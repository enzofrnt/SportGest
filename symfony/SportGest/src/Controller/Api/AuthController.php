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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'message' => 'Email ou mot de passe manquant',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $utilisateurRepository->findOneBy(['email' => $data['email']]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'message' => 'Identifiants incorrects',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Génération du token JWT
        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Vérifications des données
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

        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $sportif->getId(),
                'email' => $sportif->getEmail(),
                'nom' => $sportif->getNom(),
                'prenom' => $sportif->getPrenom(),
                'role' => 'Sportif'
            ]
        ], Response::HTTP_CREATED);
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
                'roles' => $user->getRoles(),
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
}