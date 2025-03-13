<?php

namespace App\Controller\Api;

use App\Entity\Seance;
use App\Entity\Sportif;
use App\Repository\SeanceRepository;
use App\Repository\CoachRepository;
use App\Repository\SportifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class SeanceController extends AbstractController
{
    /**
     * Recherche des créneaux disponibles
     */
    #[Route('/seances/creneaux-disponibles', methods: ['GET'])]
    public function getCreneauxDisponibles(
        Request $request,
        SeanceRepository $seanceRepository,
        CoachRepository $coachRepository
    ): JsonResponse {
        $dateDebut = new \DateTime($request->query->get('date_debut', 'now'));
        $dateFin = new \DateTime($request->query->get('date_fin', '+7 days'));
        $coachId = $request->query->get('coach_id');

        $creneaux = [];

        // Logique pour trouver les créneaux disponibles
        if ($coachId) {
            $coach = $coachRepository->find($coachId);
            if ($coach) {
                // Trouver les séances existantes pour ce coach
                $seancesExistantes = $seanceRepository->findByCoachAndDateRange($coach, $dateDebut, $dateFin);

                // Calculer les créneaux disponibles
                // Exemple simplifié - à adapter selon vos règles métier
                $creneaux = $this->calculerCreneauxDisponibles($dateDebut, $dateFin, $seancesExistantes);
            }
        } else {
            // Logique pour tous les coachs
            // ...
        }

        return $this->json($creneaux);
    }

    /**
     * Association des sportifs à une séance
     */
    #[Route('/seances/{id}/inscription-sportif', methods: ['POST'])]
    public function inscrireSportif(
        Request $request,
        Seance $seance,
        EntityManagerInterface $entityManager,
        SportifRepository $sportifRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $sportifId = $data['sportif_id'] ?? null;

        if (!$sportifId) {
            return $this->json(['error' => 'ID du sportif manquant'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $sportif = $sportifRepository->find($sportifId);
        if (!$sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier les règles métier
        if ($this->peutInscrireSportif($seance, $sportif)) {
            $seance->addSportif($sportif);
            $entityManager->flush();

            return $this->json(['message' => 'Sportif inscrit avec succès'], JsonResponse::HTTP_OK);
        }

        return $this->json(['error' => 'Le sportif ne peut pas être inscrit à cette séance'], JsonResponse::HTTP_FORBIDDEN);
    }

    /**
     * Calcul des statistiques de fréquentation
     */
    #[Route('/statistiques/frequentation', methods: ['GET'])]
    public function getStatistiquesFrequentation(SeanceRepository $seanceRepository): JsonResponse
    {
        $dateDebut = new \DateTime('-30 days');
        $dateFin = new \DateTime();

        // Statistiques générales
        $nbTotalSeances = $seanceRepository->countSeancesByDateRange($dateDebut, $dateFin);
        $nbTotalParticipants = $seanceRepository->countTotalParticipantsByDateRange($dateDebut, $dateFin);
        $moyenneParticipantsParSeance = $nbTotalSeances > 0 ? $nbTotalParticipants / $nbTotalSeances : 0;

        // Statistiques par type de séance
        $statsByType = $seanceRepository->getStatsByTypeSeance($dateDebut, $dateFin);

        // Statistiques par coach
        $statsByCoach = $seanceRepository->getStatsByCoach($dateDebut, $dateFin);

        return $this->json([
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d'),
            ],
            'general' => [
                'nb_seances' => $nbTotalSeances,
                'nb_participants' => $nbTotalParticipants,
                'moyenne_participants_par_seance' => $moyenneParticipantsParSeance,
            ],
            'par_type' => $statsByType,
            'par_coach' => $statsByCoach,
        ]);
    }

    /**
     * Méthode utilitaire pour calculer les créneaux disponibles
     */
    private function calculerCreneauxDisponibles(\DateTime $debut, \DateTime $fin, array $seancesExistantes): array
    {
        // À implémenter selon vos règles métier
        // Exemple simplifié - créneaux d'une heure de 8h à 20h
        $creneaux = [];
        $jour = clone $debut;

        while ($jour <= $fin) {
            for ($heure = 8; $heure < 20; $heure++) {
                $creneau = clone $jour;
                $creneau->setTime($heure, 0);

                $creneauFin = clone $creneau;
                $creneauFin->modify('+1 hour');

                $disponible = true;
                foreach ($seancesExistantes as $seance) {
                    $seanceDebut = $seance->getDateHeure();
                    $seanceFin = clone $seanceDebut;
                    $seanceFin->modify('+1 hour'); // Supposons que les séances durent 1 heure

                    // Vérifier si le créneau chevauche une séance existante
                    if (($creneau >= $seanceDebut && $creneau < $seanceFin) ||
                        ($creneauFin > $seanceDebut && $creneauFin <= $seanceFin) ||
                        ($creneau <= $seanceDebut && $creneauFin >= $seanceFin)
                    ) {
                        $disponible = false;
                        break;
                    }
                }

                if ($disponible) {
                    $creneaux[] = [
                        'debut' => $creneau->format('Y-m-d H:i:s'),
                        'fin' => $creneauFin->format('Y-m-d H:i:s'),
                    ];
                }
            }
            $jour->modify('+1 day');
        }

        return $creneaux;
    }

    /**
     * Méthode utilitaire pour vérifier si un sportif peut être inscrit à une séance
     */
    private function peutInscrireSportif(Seance $seance, Sportif $sportif): bool
    {
        // Vérifier le niveau requis pour la séance
        if ($sportif->getNiveauSportif()->value < $seance->getNiveauSeance()->value) {
            return false;
        }

        // Vérifier si la séance n'est pas complète (exemple : max 10 participants)
        if (count($seance->getSportifs()) >= 10) {
            return false;
        }

        return true;
    }
}