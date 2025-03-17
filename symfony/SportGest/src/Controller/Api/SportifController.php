<?php

namespace App\Controller\Api;

use App\Entity\Sportif;
use App\Repository\SeanceRepository;
use App\Repository\SportifRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/sportifs')]
class SportifController extends AbstractController
{
    #[Route('/{id}', name: 'api_sportif_show', methods: ['GET'])]
    public function showSportif(Sportif $sportif): JsonResponse
    {
        // Vérifier si l'utilisateur actuel a les droits d'accéder à ces informations
        $this->denyAccessUnlessGranted('VIEW', $sportif);

        return $this->json([
            'id' => $sportif->getId(),
            'nom' => $sportif->getNom(),
            'prenom' => $sportif->getPrenom(),
            'email' => $sportif->getEmail(),
            'niveauSportif' => $sportif->getNiveauSportif()->name,
            'dateInscription' => $sportif->getDateInscription()->format('Y-m-d'),
        ]);
    }

    #[Route('/{id}/seances', name: 'api_sportif_seances', methods: ['GET'])]
    public function getSportifSeances(Sportif $sportif, SeanceRepository $seanceRepository): JsonResponse
    {
        // Vérifier si l'utilisateur actuel a les droits d'accéder à ces informations
        $this->denyAccessUnlessGranted('VIEW', $sportif);

        // Récupérer les séances réservées par le sportif via le repository
        $seancesData = $seanceRepository->findSeancesBySportif($sportif);

        $seances = [];
        foreach ($seancesData as $seance) {
            $seances[] = [
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

        return $this->json($seances);
    }

    #[Route('/{id}/historique', name: 'api_sportif_historique', methods: ['GET'])]
    public function getSportifHistorique(Sportif $sportif, SeanceRepository $seanceRepository): JsonResponse
    {
        // Vérifier si l'utilisateur actuel a les droits d'accéder à ces informations
        $this->denyAccessUnlessGranted('VIEW', $sportif);

        // Récupérer les séances validées (historique) via le repository
        $seancesData = $seanceRepository->findSeancesTermineesBySportif($sportif);

        $historique = [];
        foreach ($seancesData as $seance) {
            $historique[] = [
                'id' => $seance->getId(),
                'themeSeance' => $seance->getThemeSeance(),
                'dateHeure' => $seance->getDateHeure()->format('Y-m-d H:i:s'),
                'typeSeance' => $seance->getTypeSeance()->name,
                'coach' => [
                    'id' => $seance->getCoach()->getId(),
                    'nom' => $seance->getCoach()->getNom(),
                    'prenom' => $seance->getCoach()->getPrenom(),
                ],
            ];
        }

        return $this->json($historique);
    }
}