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
        ReservationRepository $reservationRepository
    ): JsonResponse {
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            return $this->json(['error' => 'Réservation non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur a le droit d'annuler cette réservation
        $currentUser = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $reservation->getSportif() !== $currentUser) {
            return $this->json(['error' => 'Vous n\'avez pas les permissions nécessaires'], JsonResponse::HTTP_FORBIDDEN);
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

    #[Route('', name: 'api_reservation_create', methods: ['POST'])]
    public function createReservation(
        Request $request,
        EntityManagerInterface $entityManager,
        SeanceRepository $seanceRepository,
        SportifRepository $sportifRepository,
        ReservationRepository $reservationRepository
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
        $existingReservation = $reservationRepository->findOneBy([
            'seance' => $seance,
            'sportif' => $sportif
        ]);

        if ($existingReservation) {
            return $this->json([
                'error' => 'Le sportif est déjà inscrit à cette séance'
            ], JsonResponse::HTTP_CONFLICT);
        }

        // Vérifier si la séance n'est pas complète (limite à 10 sportifs par exemple)
        if ($seance->getReservations()->count() >= 10) {
            return $this->json([
                'error' => 'La séance est complète'
            ], JsonResponse::HTTP_CONFLICT);
        }

        // Créer la nouvelle réservation
        $reservation = new Reservation();
        $reservation->setSeance($seance);
        $reservation->setSportif($sportif);
        $reservation->setPresence(null); // Par défaut, la présence n'est pas définie

        $entityManager->persist($reservation);
        $entityManager->flush();

        return $this->json([
            'message' => 'Réservation effectuée avec succès',
            'reservation' => [
                'id' => $reservation->getId(),
                'sportif' => [
                    'id' => $sportif->getId(),
                    'nom' => $sportif->getNom(),
                    'prenom' => $sportif->getPrenom()
                ],
                'seance' => [
                    'id' => $seance->getId(),
                    'dateHeure' => $seance->getDateHeure()->format('Y-m-d H:i:s')
                ]
            ]
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/check/{seanceId}', name: 'api_check_reservation', methods: ['GET'])]
    public function checkReservation(
        int $seanceId,
        SeanceRepository $seanceRepository,
        ReservationRepository $reservationRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof Sportif) {
            return $this->json(['isReserved' => false]);
        }

        $seance = $seanceRepository->find($seanceId);
        if (!$seance) {
            return $this->json(['error' => 'Séance non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $reservation = $reservationRepository->findOneBy([
            'seance' => $seance,
            'sportif' => $user
        ]);

        if (!$reservation) {
            return $this->json(['isReserved' => false]);
        }

        return $this->json([
            'isReserved' => true,
            'reservation' => [
                'id' => $reservation->getId(),
                'presence' => $reservation->getPresence(),
                'seance' => [
                    'id' => $seance->getId(),
                    'dateHeure' => $seance->getDateHeure()->format('Y-m-d H:i:s')
                ]
            ]
        ]);
    }
}
