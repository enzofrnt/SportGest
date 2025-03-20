<?php

namespace App\Controller\Api;

use App\Entity\Seance;
use App\Entity\Sportif;
use App\Entity\Reservation;
use App\Repository\SeanceRepository;
use App\Repository\SportifRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/reservations')]
class ReservationController extends AbstractController
{

    #[Route('/{id}', name: 'api_reservation_cancel', methods: ['DELETE'])]
    public function cancelReservation(
        int $id,
        EntityManagerInterface $entityManager,
        SeanceRepository $seanceRepository,
        SportifRepository $sportifRepository,
        ReservationRepository $reservationRepository
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

        // Trouver la réservation correspondante
        $reservation = $reservationRepository->findOneBy([
            'seance' => $seance,
            'sportif' => $sportifToRemove
        ]);

        if (!$reservation) {
            return $this->json([
                'error' => 'Le sportif n\'est pas inscrit à cette séance'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Supprimer la réservation
        $entityManager->remove($reservation);
        $entityManager->flush();

        return $this->json([
            'message' => 'Réservation annulée avec succès'
        ]);
    }

    #[Route('/sportif/{id}', name: 'api_sportif_reservations', methods: ['GET'])]
    public function getSportifReservations(
        int $id,
        SportifRepository $sportifRepository,
        ReservationRepository $reservationRepository
    ): JsonResponse {
        // Vérification des permissions pour la consultation
        $this->denyAccessUnlessGranted('VIEW_RESERVATIONS', $id, 'Accès refusé : vous ne pouvez pas consulter les réservations de ce sportif');

        $sportif = $sportifRepository->find($id);
        if (!$sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $reservations = $reservationRepository->findBy(['sportif' => $sportif]);

        $reservationsData = [];
        foreach ($reservations as $reservation) {
            $seance = $reservation->getSeance();
            $coach = $seance->getCoach();
            $theme = $seance->getTheme();
            $exercices = $seance->getExercices();

            $reservationsData[] = [
                'id' => $reservation->getId(),
                'presence' => $reservation->getPresence(),
                'seance' => [
                    'id' => $seance->getId(),
                    'themeSeance' => $theme ? $theme->getNom() : null,
                    'dateHeure' => $seance->getDateHeure()->format('Y-m-d H:i:s'),
                    'typeSeance' => $seance->getTypeSeance()->value,
                    'statut' => $seance->getStatut()->value,
                    'coach' => [
                        'id' => $coach->getId(),
                        'nom' => $coach->getNom(),
                        'prenom' => $coach->getPrenom(),
                        'email' => $coach->getEmail()
                    ],
                    'exercices' => array_map(function($exercice) {
                        return [
                            'id' => $exercice->getId(),
                            'nom' => $exercice->getNom(),
                            'description' => $exercice->getDescription(),
                            'difficulte' => $exercice->getDifficulte()->value,
                            'dureeEstimee' => $exercice->getDureeEstimee()
                        ];
                    }, $exercices->toArray())
                ],
                'sportif' => [
                    'id' => $sportif->getId(),
                    'nom' => $sportif->getNom(),
                    'prenom' => $sportif->getPrenom(),
                    'email' => $sportif->getEmail(),
                    'dateInscription' => $sportif->getDateInscription()->format('Y-m-d'),
                    'niveau' => $sportif->getNiveauSportif()->value
                ]
            ];
        }

        return $this->json($reservationsData);
    }
}
