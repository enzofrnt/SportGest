<?php

namespace App\Controller\Api;

use App\Entity\Sportif;
use App\Entity\Utilisateur;
use App\Repository\SeanceRepository;
use App\Repository\SportifRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/bilans')]
class BilanController extends AbstractController
{
    #[Route('/{sportifId}', name: 'api_bilan_sportif', methods: ['GET'])]
    public function getBilanSportif(
        int $sportifId,
        Request $request,
        SportifRepository $sportifRepository,
        SeanceRepository $seanceRepository
    ): JsonResponse {
        // Vérifier si le sportif existe
        $sportif = $sportifRepository->find($sportifId);
        if (!$sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur est authentifié
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Vous devez être authentifié pour accéder à cette ressource'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Vérifier les droits d'accès (soit l'utilisateur lui-même, soit un coach, soit un admin)
        /** @var Utilisateur $currentUser */
        if (
            $currentUser->getId() != $sportifId &&
            !$this->isGranted('ROLE_COACH') &&
            !$this->isGranted('ROLE_ADMIN')
        ) {
            return $this->json(['error' => 'Accès non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Récupérer les paramètres de la requête
        $dateMin = $request->query->get('date_min') ? new \DateTime($request->query->get('date_min')) : new \DateTime('-30 days');
        $dateMax = $request->query->get('date_max') ? new \DateTime($request->query->get('date_max')) : new \DateTime();

        // Récupérer les séances validées du sportif dans la période demandée
        $seances = $seanceRepository->findSeancesValideesBySportifAndDates($sportif, $dateMin, $dateMax);

        // Calculer les statistiques
        $totalSeances = count($seances);
        $totalDuree = 0;
        $exercicesRealises = [];
        $coachs = [];
        $typesSeances = [];

        foreach ($seances as $seance) {
            // Durée totale (on suppose 1 heure par séance par défaut, à ajuster selon votre modèle)
            $totalDuree += 60; // en minutes

            // Collecter les types de séances
            $typeSeance = $seance->getTypeSeance()->name;
            if (!isset($typesSeances[$typeSeance])) {
                $typesSeances[$typeSeance] = 0;
            }
            $typesSeances[$typeSeance]++;

            // Collecter les coachs
            $coach = $seance->getCoach();
            $coachId = $coach->getId();
            if (!isset($coachs[$coachId])) {
                $coachs[$coachId] = [
                    'id' => $coachId,
                    'nom' => $coach->getNom(),
                    'prenom' => $coach->getPrenom(),
                    'nbSeances' => 0
                ];
            }
            $coachs[$coachId]['nbSeances']++;

            // Collecter les exercices
            foreach ($seance->getExercices() as $exercice) {
                $exerciceId = $exercice->getId();
                if (!isset($exercicesRealises[$exerciceId])) {
                    $exercicesRealises[$exerciceId] = [
                        'id' => $exerciceId,
                        'nom' => $exercice->getNom(),
                        'difficulte' => $exercice->getDifficulte()->name,
                        'nbFois' => 0
                    ];
                }
                $exercicesRealises[$exerciceId]['nbFois']++;
            }
        }

        // Formatage des données pour le résultat
        $typesSeancesArray = [];
        foreach ($typesSeances as $type => $count) {
            $typesSeancesArray[] = ['type' => $type, 'nbSeances' => $count];
        }

        // Préparer le bilan complet
        $bilan = [
            'sportif' => [
                'id' => $sportif->getId(),
                'nom' => $sportif->getNom(),
                'prenom' => $sportif->getPrenom(),
                'niveauSportif' => $sportif->getNiveauSportif()->name
            ],
            'periode' => [
                'debut' => $dateMin->format('Y-m-d'),
                'fin' => $dateMax->format('Y-m-d')
            ],
            'statistiques' => [
                'nbSeances' => $totalSeances,
                'dureeTotal' => $totalDuree,
                'moyenneHebdo' => $this->calculerMoyenneHebdo($totalSeances, $dateMin, $dateMax)
            ],
            'typesSeances' => $typesSeancesArray,
            'coachs' => array_values($coachs),
            'exercices' => array_values($exercicesRealises)
        ];

        return $this->json($bilan);
    }

    private function calculerMoyenneHebdo(int $totalSeances, \DateTime $dateDebut, \DateTime $dateFin): float
    {
        $intervalle = $dateDebut->diff($dateFin);
        $joursTotal = $intervalle->days + 1; // +1 pour inclure le jour de fin
        $semainesTotal = $joursTotal / 7;

        // Éviter division par zéro
        if ($semainesTotal <= 0) {
            return 0;
        }

        return round($totalSeances / $semainesTotal, 2);
    }
}