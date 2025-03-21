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
        $seances = $seanceRepository->findSeancesBySportif($sportif);

        // Calculer les statistiques
        $totalSeances = count($seances);
        $totalDuree = 0;
        $topExercicesData = []; // Pour calculer le top 3
        $coachs = [];
        $typesSeances = [];
        $detailsSeances = [];

        foreach ($seances as $seance) {
            // Calculer la durée de la séance en fonction des exercices
            $dureeSeance = 0;
            $exercicesSeance = [];

            foreach ($seance->getExercices() as $exercice) {
                $exerciceId = $exercice->getId();
                $dureeExercice = $exercice->getDureeEstimee();
                $dureeSeance += $dureeExercice;

                // Ajouter aux exercices de cette séance avec tous les détails possibles
                $exercicesSeance[] = [
                    'id' => $exerciceId,
                    'nom' => $exercice->getNom(),
                    'description' => $exercice->getDescription(),
                    'duree' => $dureeExercice,
                    'difficulte' => $exercice->getDifficulte()->value
                ];

                // Collecter uniquement pour le top 3
                if (!isset($topExercicesData[$exerciceId])) {
                    $topExercicesData[$exerciceId] = [
                        'id' => $exerciceId,
                        'nom' => $exercice->getNom(),
                        'difficulte' => $exercice->getDifficulte()->value,
                        'nbFois' => 0,
                        'dureeTotal' => 0
                    ];
                }
                $topExercicesData[$exerciceId]['nbFois']++;
                $topExercicesData[$exerciceId]['dureeTotal'] += $dureeExercice;
            }

            // Ajouter la durée de cette séance au total
            $totalDuree += $dureeSeance;

            // Calculer l'heure de fin de la séance
            $dateHeureDebut = $seance->getDateHeure();
            $dateHeureFin = clone $dateHeureDebut;
            $dateHeureFin->modify("+{$dureeSeance} minutes");

            // Collecter les types de séances
            $typeSeance = $seance->getTypeSeance()->value;
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
                    'nbSeances' => 0,
                    'dureeTotal' => 0
                ];
            }
            $coachs[$coachId]['nbSeances']++;
            $coachs[$coachId]['dureeTotal'] += $dureeSeance;

            // Ajouter les détails de cette séance
            $detailsSeances[] = [
                'id' => $seance->getId(),
                'dateDebut' => $dateHeureDebut->format('Y-m-d H:i:s'),
                'dateFin' => $dateHeureFin->format('Y-m-d H:i:s'),
                'duree' => $dureeSeance,
                'typeSeance' => $typeSeance,
                'theme' => $seance->getTheme() ? $seance->getTheme()->getNom() : null,
                'niveauSeance' => $seance->getNiveauSeance()->value,
                'coach' => [
                    'id' => $coach->getId(),
                    'nom' => $coach->getNom(),
                    'prenom' => $coach->getPrenom()
                ],
                'exercices' => $exercicesSeance,
                'nbExercices' => count($exercicesSeance)
            ];
        }

        // Tri des exercices pour obtenir le top 3
        usort($topExercicesData, function ($a, $b) {
            return $b['nbFois'] <=> $a['nbFois'];
        });
        $topExercices = array_slice(array_values($topExercicesData), 0, 3);

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
                'niveauSportif' => $sportif->getNiveauSportif()->value,
                'dateInscription' => $sportif->getDateInscription()->format('Y-m-d H:i:s')
            ],
            'periode' => [
                'debut' => $dateMin->format('Y-m-d'),
                'fin' => $dateMax->format('Y-m-d')
            ],
            'statistiques' => [
                'nbSeances' => $totalSeances,
                'dureeTotal' => $totalDuree, // en minutes
                'moyenneHebdo' => $this->calculerMoyenneHebdo($totalSeances, $dateMin, $dateMax)
            ],
            'typesSeances' => $typesSeancesArray,
            'coachs' => array_values($coachs),
            'topExercices' => $topExercices,
            'seances' => $detailsSeances
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