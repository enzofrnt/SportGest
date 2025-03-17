<?php

namespace App\Controller\Api;

use App\Entity\Coach;
use App\Enum\StatutSeance;
use App\Repository\CoachRepository;
use App\Repository\SeanceRepository;
use App\Repository\SportifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/stats')]
class StatistiqueController extends AbstractController
{
    #[Route('/frequentation', name: 'api_stats_frequentation', methods: ['GET'])]
    public function getFrequentation(
        Request $request,
        SeanceRepository $seanceRepository
    ): JsonResponse {
        // Période d'analyse (par défaut les 30 derniers jours)
        $dateDebut = $request->query->get('date_debut')
            ? new \DateTime($request->query->get('date_debut'))
            : new \DateTime('-30 days');
        $dateFin = $request->query->get('date_fin')
            ? new \DateTime($request->query->get('date_fin'))
            : new \DateTime();

        // Statistiques de fréquentation
        $nbSeances = $seanceRepository->countSeancesByDateRange($dateDebut, $dateFin);
        $nbParticipants = $seanceRepository->countParticipationsByDateRange($dateDebut, $dateFin);
        $moyenneParticipants = $nbSeances > 0 ? $nbParticipants / $nbSeances : 0;

        // Répartition par jour de la semaine
        $repartitionJours = $seanceRepository->getDistributionByDayOfWeek($dateDebut, $dateFin);

        // Répartition par heure de la journée
        $repartitionHeures = $seanceRepository->getDistributionByHourOfDay($dateDebut, $dateFin);

        // Évolution au fil du temps (nombre de séances par semaine)
        $evolution = $seanceRepository->getWeeklyEvolution($dateDebut, $dateFin);

        return $this->json([
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d')
            ],
            'statistiques_globales' => [
                'nb_seances' => $nbSeances,
                'nb_participants_total' => $nbParticipants,
                'moyenne_participants_par_seance' => round($moyenneParticipants, 2),
                'taux_remplissage' => round(($moyenneParticipants / 10) * 100, 2) . '%' // Hypothèse de 10 places max par séance
            ],
            'repartition_jours' => $repartitionJours,
            'repartition_heures' => $repartitionHeures,
            'evolution_frequentation' => $evolution
        ]);
    }

    #[Route('/coachs', name: 'api_stats_coachs', methods: ['GET'])]
    public function getStatsCoach(
        Request $request,
        SeanceRepository $seanceRepository,
        CoachRepository $coachRepository
    ): JsonResponse {
        // Période d'analyse (par défaut les 30 derniers jours)
        $dateDebut = $request->query->get('date_debut')
            ? new \DateTime($request->query->get('date_debut'))
            : new \DateTime('-30 days');
        $dateFin = $request->query->get('date_fin')
            ? new \DateTime($request->query->get('date_fin'))
            : new \DateTime();

        // Récupérer tous les coachs
        $coachs = $coachRepository->findAll();

        $statsPerCoach = [];
        foreach ($coachs as $coach) {
            $nbSeances = $seanceRepository->countSeancesByCoachAndDateRange($coach, $dateDebut, $dateFin);
            $nbParticipants = $seanceRepository->countParticipantsByCoachAndDateRange($coach, $dateDebut, $dateFin);
            $moyenneParticipants = $nbSeances > 0 ? $nbParticipants / $nbSeances : 0;

            $statsPerCoach[] = [
                'coach' => [
                    'id' => $coach->getId(),
                    'nom' => $coach->getNom(),
                    'prenom' => $coach->getPrenom()
                ],
                'nb_seances' => $nbSeances,
                'nb_participants_total' => $nbParticipants,
                'moyenne_participants_par_seance' => round($moyenneParticipants, 2),
                'taux_remplissage' => round(($moyenneParticipants / 10) * 100, 2) . '%'
            ];
        }

        // Trier par nombre de séances décroissant
        usort($statsPerCoach, function ($a, $b) {
            return $b['nb_seances'] <=> $a['nb_seances'];
        });

        return $this->json([
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d')
            ],
            'statistiques_coachs' => $statsPerCoach
        ]);
    }

    #[Route('/creneaux', name: 'api_stats_creneaux', methods: ['GET'])]
    public function getStatsCreneaux(
        Request $request,
        SeanceRepository $seanceRepository
    ): JsonResponse {
        // Période d'analyse (par défaut les 30 derniers jours)
        $dateDebut = $request->query->get('date_debut')
            ? new \DateTime($request->query->get('date_debut'))
            : new \DateTime('-30 days');
        $dateFin = $request->query->get('date_fin')
            ? new \DateTime($request->query->get('date_fin'))
            : new \DateTime();

        // Statistiques par créneaux horaires (par heure de la journée)
        $statsParCreneau = $seanceRepository->getStatsPerTimeSlot($dateDebut, $dateFin);

        // Statistiques des jours les plus fréquentés
        $statsParsJours = $seanceRepository->getStatsPerDayOfWeek($dateDebut, $dateFin);

        // Le meilleur créneau (heure + jour) en termes de participation
        $meilleurCreneau = $seanceRepository->getBestTimeSlot($dateDebut, $dateFin);

        return $this->json([
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d')
            ],
            'stats_par_creneau' => $statsParCreneau,
            'stats_par_jour' => $statsParsJours,
            'meilleur_creneau' => $meilleurCreneau
        ]);
    }

    #[Route('/absenteisme', name: 'api_stats_absenteisme', methods: ['GET'])]
    public function getStatsAbsenteisme(
        Request $request,
        SeanceRepository $seanceRepository,
        SportifRepository $sportifRepository
    ): JsonResponse {
        // Période d'analyse (par défaut les 30 derniers jours)
        $dateDebut = $request->query->get('date_debut')
            ? new \DateTime($request->query->get('date_debut'))
            : new \DateTime('-30 days');
        $dateFin = $request->query->get('date_fin')
            ? new \DateTime($request->query->get('date_fin'))
            : new \DateTime();

        // Taux d'absentéisme global (séances réservées vs séances auxquelles les sportifs ont réellement assisté)
        $nbReservations = $seanceRepository->countReservationsByDateRange($dateDebut, $dateFin);
        $nbPresences = $seanceRepository->countPresentsByDateRange($dateDebut, $dateFin);
        $tauxAbsenteisme = $nbReservations > 0 ? 100 - (($nbPresences / $nbReservations) * 100) : 0;

        // Absentéisme par jour de la semaine
        $absenteismeParJour = $seanceRepository->getAbsenteeismByDayOfWeek($dateDebut, $dateFin);

        // Absentéisme par type de séance
        $absenteismeParType = $seanceRepository->getAbsenteeismBySessionType($dateDebut, $dateFin);

        // Top 5 des sportifs avec le plus fort taux d'absentéisme
        $sportifs = $sportifRepository->findAll();
        $absenteismeParSportif = [];

        foreach ($sportifs as $sportif) {
            $reservations = $seanceRepository->countReservationsBySportifAndDateRange($sportif, $dateDebut, $dateFin);
            if ($reservations > 0) {
                $presences = $seanceRepository->countPresentsBySportifAndDateRange($sportif, $dateDebut, $dateFin);
                $taux = 100 - (($presences / $reservations) * 100);
                $absenteismeParSportif[] = [
                    'sportif' => [
                        'id' => $sportif->getId(),
                        'nom' => $sportif->getNom(),
                        'prenom' => $sportif->getPrenom()
                    ],
                    'nb_reservations' => $reservations,
                    'nb_presences' => $presences,
                    'taux_absenteisme' => round($taux, 2) . '%'
                ];
            }
        }

        // Trier par taux d'absentéisme décroissant
        usort($absenteismeParSportif, function ($a, $b) {
            return $b['taux_absenteisme'] <=> $a['taux_absenteisme'];
        });

        // Limiter aux 5 premiers
        $absenteismeParSportif = array_slice($absenteismeParSportif, 0, 5);

        return $this->json([
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d')
            ],
            'statistiques_globales' => [
                'nb_reservations' => $nbReservations,
                'nb_presences' => $nbPresences,
                'taux_absenteisme_global' => round($tauxAbsenteisme, 2) . '%'
            ],
            'absenteisme_par_jour' => $absenteismeParJour,
            'absenteisme_par_type' => $absenteismeParType,
            'top_absenteisme_sportifs' => $absenteismeParSportif
        ]);
    }

    #[Route('/popularite', name: 'api_stats_popularite', methods: ['GET'])]
    public function getStatsPopularite(
        Request $request,
        SeanceRepository $seanceRepository
    ): JsonResponse {
        // Période d'analyse (par défaut les 30 derniers jours)
        $dateDebut = $request->query->get('date_debut')
            ? new \DateTime($request->query->get('date_debut'))
            : new \DateTime('-30 days');
        $dateFin = $request->query->get('date_fin')
            ? new \DateTime($request->query->get('date_fin'))
            : new \DateTime();

        // Top 10 des séances les plus populaires (basé sur le taux de remplissage)
        $seancesPopulaires = $seanceRepository->getTopPopularSessions($dateDebut, $dateFin, 10);

        // Classement des types de séances par popularité
        $populariteParType = $seanceRepository->getPopularityBySessionType($dateDebut, $dateFin);

        // Thèmes de séances les plus populaires
        $populariteParTheme = $seanceRepository->getPopularityByTheme($dateDebut, $dateFin);

        return $this->json([
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d')
            ],
            'top_seances' => $seancesPopulaires,
            'popularite_par_type' => $populariteParType,
            'popularite_par_theme' => $populariteParTheme
        ]);
    }
}