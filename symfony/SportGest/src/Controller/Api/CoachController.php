<?php

namespace App\Controller\Api;

use App\Entity\Coach;
use App\Entity\Seance;
use App\Repository\CoachRepository;
use App\Repository\SeanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/coachs')]
class CoachController extends AbstractController
{

    private function getCoachData(Coach $coach): array
    {
        return [
            'id' => $coach->getId(),
            'nom' => $coach->getNom(),
            'prenom' => $coach->getPrenom(),
            'email' => $coach->getEmail(),
            'tarifHoraire' => $coach->getTarifHoraire(),
            'specialites' => $coach->getSpecialite()->map(fn($specialite) => [
                'id' => $specialite->getId(),
                'nom' => $specialite->getNom()
            ])->toArray(),
        ];
    }

    
    #[Route('', name: 'api_coachs_list', methods: ['GET'])]
    public function listCoachs(CoachRepository $coachRepository): JsonResponse
    {
        $coachs = $coachRepository->findAll();

        // Transformer les données pour l'API
        $data = [];

        foreach ($coachs as $coach) {
            $data[] = $this->getCoachData($coach);
        }

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_coach_show', methods: ['GET'])]
    public function showCoach(Coach $coach): JsonResponse
    {
        return $this->json($this->getCoachData($coach));
    }

    #[Route('/{id}/specialites', name: 'api_coach_specialites', methods: ['GET'])]
    public function getCoachSpecialites(Coach $coach): JsonResponse
    {
        // Récupérer les spécialités du coach
        $specialites = $coach->getSpecialite();

        return $this->json($specialites);
    }

    #[Route('/{id}/seances', name: 'api_coach_seances', methods: ['GET'])]
    public function getCoachSeances(Coach $coach, SeanceRepository $seanceRepository): JsonResponse
    {
        // Récupérer les séances proposées par le coach
        $seances = $seanceRepository->findBy(['coach' => $coach]);

        $data = [];
        foreach ($seances as $seance) {
            $data[] = [
                'id' => $seance->getId(),
                'theme' => [
                    'id' => $seance->getTheme()->getId(),
                    'nom' => $seance->getTheme()->getNom()
                ],
                'dateHeure' => $seance->getDateHeure()->format('Y-m-d H:i:s'),
                'typeSeance' => $seance->getTypeSeance()->value,
                'statut' => $seance->getStatut()->value,
                'niveauSeance' => $seance->getNiveauSeance()->value,
                'nbSportifs' => $seance->getReservations()->count(),
            ];
        }

        return $this->json($data);
    }
}