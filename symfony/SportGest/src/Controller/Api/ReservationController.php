<?php

namespace App\Controller\Api;

use App\Entity\Seance;
use App\Entity\Sportif;
use App\Repository\SeanceRepository;
use App\Repository\SportifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/reservations')]
class ReservationController extends AbstractController
{
    #[Route('', name: 'api_reservation_create', methods: ['POST'])]
    public function createReservation(
        Request $request,
        EntityManagerInterface $entityManager,
        SeanceRepository $seanceRepository,
        SportifRepository $sportifRepository
    ): JsonResponse {
        // Vérification des permissions
        $this->denyAccessUnlessGranted('CREATE_RESERVATION', null, 'Accès refusé : seuls les sportifs peuvent réserver une séance');

        $data = json_decode($request->getContent(), true);

        if (!isset($data['seance_id'])) {
            return $this->json(['error' => 'ID de séance non fourni'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $seance = $seanceRepository->find($data['seance_id']);
        if (!$seance) {
            return $this->json(['error' => 'Séance non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Par défaut, on utilise l'utilisateur connecté pour la réservation
        /** @var Sportif $sportif */
        $sportif = $this->getUser();

        // Si un administrateur effectue la réservation pour quelqu'un d'autre
        if (isset($data['sportif_id']) && $this->isGranted('ROLE_ADMIN')) {
            $sportif = $sportifRepository->find($data['sportif_id']);
            if (!$sportif) {
                return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
            }
        } else if (isset($data['sportif_id'])) {
            // Si un non-admin tente de réserver pour quelqu'un d'autre
            return $this->json(['error' => 'Vous ne pouvez pas créer une réservation pour un autre sportif'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Assurons-nous que l'utilisateur est bien un sportif
        if (!($sportif instanceof Sportif)) {
            return $this->json(['error' => 'Seuls les sportifs peuvent réserver des séances'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Vérifier si le sportif est déjà inscrit
        if ($seance->getSportifs()->contains($sportif)) {
            return $this->json([
                'error' => 'Le sportif est déjà inscrit à cette séance'
            ], JsonResponse::HTTP_CONFLICT);
        }

        // Vérifier si la séance n'est pas complète (limite à 10 sportifs par exemple)
        if ($seance->getSportifs()->count() >= 10) {
            return $this->json([
                'error' => 'La séance est complète'
            ], JsonResponse::HTTP_CONFLICT);
        }

        // Ajouter le sportif à la séance
        $seance->addSportif($sportif);
        $entityManager->flush();

        return $this->json([
            'message' => 'Réservation effectuée avec succès'
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_reservation_cancel', methods: ['DELETE'])]
    public function cancelReservation(
        int $id, // ID de la séance dans l'URL
        EntityManagerInterface $entityManager,
        SeanceRepository $seanceRepository,
        SportifRepository $sportifRepository
    ): JsonResponse {
        // Récupérer la séance à partir de l'ID dans l'URL
        $seance = $seanceRepository->find($id);
        if (!$seance) {
            return $this->json(['error' => 'Séance non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer l'utilisateur connecté
        $currentUser = $this->getUser();

        // Déterminer quel sportif doit être retiré
        /** @var Sportif $sportifToRemove */
        $sportifToRemove = null;

        // Si l'utilisateur est un sportif, il ne peut annuler que sa propre réservation
        if ($currentUser instanceof Sportif) {
            $sportifToRemove = $currentUser;
        } else {
            // Pour les admins et responsables, on pourrait permettre d'annuler n'importe quelle réservation
            // mais on utiliserait un paramètre de requête pour cela, comme ?sportif_id=123
            if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_RESPONSABLE')) {
                return $this->json(['error' => 'Vous n\'avez pas les permissions nécessaires'], JsonResponse::HTTP_FORBIDDEN);
            }

            // Dans ce cas simpliste, on considère que seul le sportif connecté peut annuler
            $sportifToRemove = $currentUser;
        }

        // Vérifier si le sportif est bien inscrit à cette séance
        if (!$seance->getSportifs()->contains($sportifToRemove)) {
            return $this->json([
                'error' => 'Le sportif n\'est pas inscrit à cette séance'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Retirer le sportif de la séance
        $seance->removeSportif($sportifToRemove);
        $entityManager->flush();

        return $this->json([
            'message' => 'Réservation annulée avec succès'
        ]);
    }

    #[Route('/sportif/{id}', name: 'api_sportif_reservations', methods: ['GET'])]
    public function getSportifReservations(
        int $id,
        SportifRepository $sportifRepository,
        SeanceRepository $seanceRepository
    ): JsonResponse {
        // Vérification des permissions pour la consultation
        $this->denyAccessUnlessGranted('VIEW_RESERVATIONS', $id, 'Accès refusé : vous ne pouvez pas consulter les réservations de ce sportif');

        $sportif = $sportifRepository->find($id);
        if (!$sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $seances = $seanceRepository->findSeancesBySportif($sportif);

        $reservations = [];
        foreach ($seances as $seance) {
            $reservations[] = [
                'id' => $seance->getId(),
                'themeSeance' => $seance->getThemeSeance(),
                'dateHeure' => $seance->getDateHeure()->format('Y-m-d H:i:s'),
                'typeSeance' => $seance->getTypeSeance()->name,
                'statut' => $seance->getStatut()->name,
                'coach' => [
                    'id' => $seance->getCoach()->getId(),
                    'nom' => $seance->getCoach()->getNom(),
                    'prenom' => $seance->getCoach()->getPrenom(),
                ],
            ];
        }

        return $this->json($reservations);
    }
}
