<?php

namespace App\Controller\Api;

use App\Entity\Sportif;
use App\Entity\Utilisateur;
use App\Enum\NiveauSportif;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    private const TOKEN_COOKIE_NAME = 'BEARER';
    private const TOKEN_EXPIRATION = 3600; // 1 heure en secondes

    private function addCorsHeaders(JsonResponse $response): void
    {
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:4200');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '3600');
        $response->headers->set('Access-Control-Expose-Headers', 'Set-Cookie');
        $response->headers->set('Vary', 'Origin');
    }

    private function createSecureCookie(string $token): Cookie
    {
        return Cookie::create(self::TOKEN_COOKIE_NAME)
            ->withValue($token)
            ->withExpires(time() + self::TOKEN_EXPIRATION)
            ->withPath('/')
            ->withHttpOnly(true)
            ->withSecure(false) // Changé à false pour le développement local
            ->withSameSite('lax'); // Changé à 'lax' pour permettre les requêtes cross-origin
    }

    #[Route('/login', name: 'api_login', methods: ['POST', 'OPTIONS'])]
    public function login(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse();
            $this->addCorsHeaders($response);
            return $response;
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            $response = $this->json([
                'message' => 'Veuillez remplir tous les champs',
                'error' => 'missing_fields'
            ], Response::HTTP_BAD_REQUEST);
            $this->addCorsHeaders($response);
            return $response;
        }

        $user = $utilisateurRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            $response = $this->json([
                'message' => 'Aucun compte n\'existe avec cet email',
                'error' => 'user_not_found'
            ], Response::HTTP_UNAUTHORIZED);
            $this->addCorsHeaders($response);
            return $response;
        }

        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            $response = $this->json([
                'message' => 'Mot de passe incorrect',
                'error' => 'invalid_password'
            ], Response::HTTP_UNAUTHORIZED);
            $this->addCorsHeaders($response);
            return $response;
        }

        // Génération du token JWT
        $token = $jwtManager->create($user);

        $response = $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'roles' => array_values($user->getRoles())
            ]
        ]);

        $this->addCorsHeaders($response);
        return $response;
    }

    #[Route('/verify', name: 'api_verify_token', methods: ['GET', 'OPTIONS'])]
    public function verifyToken(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse();
            $this->addCorsHeaders($response);
            return $response;
        }

        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            $response = $this->json([
                'message' => 'Non authentifié',
                'valid' => false
            ], Response::HTTP_UNAUTHORIZED);
            $this->addCorsHeaders($response);
            return $response;
        }

        $response = $this->json([
            'valid' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'roles' => $user->getRoles()
            ]
        ]);
        
        $this->addCorsHeaders($response);
        return $response;
    }

    #[Route('/refresh-token', name: 'api_refresh_token', methods: ['POST'])]
    public function refreshToken(
        Request $request,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Génération d'un nouveau token
        $token = $jwtManager->create($user);

        // Création du nouveau cookie
        $cookie = Cookie::create(self::TOKEN_COOKIE_NAME)
            ->withValue($token)
            ->withExpires(time() + self::TOKEN_EXPIRATION)
            ->withPath('/')
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite('none');

        $response = $this->json(['message' => 'Token rafraîchi avec succès']);
        $response->headers->setCookie($cookie);
        return $response;
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = $this->json(['message' => 'Déconnecté avec succès']);
        $this->addCorsHeaders($response);
        return $response;
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['nom']) || !isset($data['prenom'])) {
            return $this->json(['message' => 'Données incomplètes'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        $userRepository = $entityManager->getRepository(Utilisateur::class);
        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['message' => 'Cet email est déjà utilisé'], Response::HTTP_CONFLICT);
        }

        // Création du sportif
        $sportif = new Sportif();
        $sportif->setEmail($data['email']);
        $sportif->setNom($data['nom']);
        $sportif->setPrenom($data['prenom']);
        $sportif->setRoles(['ROLE_USER', 'ROLE_SPORTIF']);
        $sportif->setDateInscription(new \DateTimeImmutable());
        $sportif->setNiveauSportif(NiveauSportif::DEBUTANT);

        // Hasher le mot de passe
        $hashedPassword = $passwordHasher->hashPassword(
            $sportif,
            $data['password']
        );
        $sportif->setPassword($hashedPassword);

        // Validation
        $errors = $validator->validate($sportif);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Enregistrer en base de données
        $entityManager->persist($sportif);
        $entityManager->flush();

        // Génération du token JWT
        $token = $jwtManager->create($sportif);

        $response = $this->json([
            'message' => 'Utilisateur créé avec succès',
            'token' => $token,
            'user' => [
                'id' => $sportif->getId(),
                'email' => $sportif->getEmail(),
                'nom' => $sportif->getNom(),
                'prenom' => $sportif->getPrenom(),
                'roles' => array_values($sportif->getRoles())
            ]
        ], Response::HTTP_CREATED);

        $this->addCorsHeaders($response);
        return $response;
    }

    #[Route('/user', name: 'api_user_info', methods: ['GET'])]
    public function getUserInfo(): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (null === $user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'roles' => array_values($user->getRoles()),
                'role' => $user->getRole()
            ]
        ]);
    }

    #[Route('/user', name: 'api_user_update', methods: ['PUT'])]
    public function updateUserInfo(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (null === $user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Mise à jour des informations
        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }

        if (isset($data['prenom'])) {
            $user->setPrenom($data['prenom']);
        }

        if (isset($data['email'])) {
            // Vérifier si l'email n'est pas déjà utilisé par un autre utilisateur
            $userRepository = $entityManager->getRepository(Utilisateur::class);
            $existingUser = $userRepository->findOneBy(['email' => $data['email']]);

            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return $this->json(['message' => 'Cet email est déjà utilisé'], Response::HTTP_CONFLICT);
            }

            $user->setEmail($data['email']);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $data['password']
            );
            $user->setPassword($hashedPassword);
        }

        // Ajout de validations spécifiques pour les Sportifs
        if ($user instanceof Sportif && isset($data['niveauSportif'])) {
            try {
                $niveau = NiveauSportif::from($data['niveauSportif']);
                $user->setNiveauSportif($niveau);
            } catch (\ValueError $e) {
                return $this->json(['message' => 'Niveau sportif invalide'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validation
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Informations mises à jour avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'roles' => $user->getRoles(),
                'role' => $user->getRole()
            ]
        ]);
    }

    #[Route('/check-admin', name: 'api_check_admin', methods: ['GET'])]
    public function checkAdmin(): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier si l'utilisateur a le rôle ROLE_ADMIN
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Accès non autorisée'], Response::HTTP_FORBIDDEN);
        }

        return $this->json(['message' => 'Authentifié en tant qu\'administrateur']);
    }
}