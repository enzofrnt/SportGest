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
    #[IsGranted('ROLE_SPORTIF')]
    public function createReservation(
        Request $request,
        EntityManagerInterface $entityManager,
        SeanceRepository $seanceRepository,
        SportifRepository $sportifRepository
    ): JsonResponse {
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
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        SeanceRepository $seanceRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['sportif_id']) || !isset($data['seance_id'])) {
            return $this->json(['error' => 'Données incomplètes'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $seance = $seanceRepository->find($data['seance_id']);
        if (!$seance) {
            return $this->json(['error' => 'Séance non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var Sportif $currentUser */
        $currentUser = $this->getUser();

        // Vérifier si l'utilisateur a le droit d'annuler cette réservation
        if ($currentUser->getId() != $data['sportif_id'] && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Trouver le sportif à retirer
        $sportifToRemove = null;
        foreach ($seance->getSportifs() as $sportif) {
            if ($sportif->getId() == $data['sportif_id']) {
                $sportifToRemove = $sportif;
                break;
            }
        }

        if (!$sportifToRemove) {
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
        $sportif = $sportifRepository->find($id);
        if (!$sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur a le droit d'accéder à ces informations
        /** @var Sportif $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser->getId() != $id && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
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