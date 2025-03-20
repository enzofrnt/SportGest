<?php

namespace App\Controller\Api;

use App\Entity\Seance;
use App\Entity\Sportif;
use App\Entity\Coach;
use App\Enum\StatutSeance;
use App\Repository\SeanceRepository;
use App\Repository\CoachRepository;
use App\Repository\SportifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
            // Cas où un coach spécifique est demandé
            $coach = $coachRepository->find($coachId);
            if ($coach) {
                // Trouver les séances existantes pour ce coach
                $seancesExistantes = $seanceRepository->findByCoachAndDateRange($coach, $dateDebut, $dateFin);

                // Calculer les créneaux disponibles
                $creneaux = $this->calculerCreneauxDisponibles($dateDebut, $dateFin, $seancesExistantes);
            }
        } else {
            // Logique pour tous les coachs si aucun coach_id n'est fourni
            $tousLesCoaches = $coachRepository->findAll();
            $toutesSeances = [];

            foreach ($tousLesCoaches as $coach) {
                $seancesCoach = $seanceRepository->findByCoachAndDateRange($coach, $dateDebut, $dateFin);
                $toutesSeances = array_merge($toutesSeances, $seancesCoach);
            }

            // Calculer les créneaux disponibles pour tous les coachs
            $creneaux = $this->calculerCreneauxDisponibles($dateDebut, $dateFin, $toutesSeances);
        }

        // Formater la réponse selon la structure attendue: [ { "date": "string", "creneaux": ["string"] }, ... ]
        $creneauxParJour = [];

        foreach ($creneaux as $creneau) {
            $date = substr($creneau['debut'], 0, 10); // Extraire la date (YYYY-MM-DD)
            $heure = substr($creneau['debut'], 11, 5); // Extraire l'heure (HH:MM)

            if (!isset($creneauxParJour[$date])) {
                $creneauxParJour[$date] = [
                    'date' => $date,
                    'creneaux' => []
                ];
            }

            $creneauxParJour[$date]['creneaux'][] = $heure;
        }

        return $this->json(array_values($creneauxParJour));
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

    private function getSeanceData(Seance $seance): array
    {
        return [
            'id' => $seance->getId(),
            'theme' => [
                'id' => $seance->getTheme()->getId(),
                'nom' => $seance->getTheme()->getNom()
            ],
            'dateHeure' => $seance->getDateHeure()->format('Y-m-d H:i:s'),
            'typeSeance' => $seance->getTypeSeance()->name,
            'statut' => $seance->getStatut()->name,
            'niveauSeance' => $seance->getNiveauSeance()->name,
            'coach' => [
                'id' => $seance->getCoach()->getId(),
                'nom' => $seance->getCoach()->getNom(),
                'prenom' => $seance->getCoach()->getPrenom(),
            ],
            'nbSportifs' => $seance->getSportifs()->count(),
            'exercices' => array_map(function($exercice) {
                return [
                    'id' => $exercice->getId(),
                    'nom' => $exercice->getNom()
                ];
            }, $seance->getExercices()->toArray())
        ];
    }

    #[Route('/seances', name: 'api_seances_list', methods: ['GET'])]
    public function listSeances(SeanceRepository $seanceRepository, Request $request): JsonResponse
    {
        // Récupération des paramètres de filtrage
        $typeSeance = $request->query->get('type_seance');
        $niveauSeance = $request->query->get('niveau_seance');
        $dateMin = $request->query->get('date_min') ? new \DateTime($request->query->get('date_min')) : null;
        $dateMax = $request->query->get('date_max') ? new \DateTime($request->query->get('date_max')) : null;
        $statut = $request->query->get('statut');
        $coachId = $request->query->get('coach_id');

        // Construction de la requête avec QueryBuilder
        $qb = $seanceRepository->createQueryBuilder('s')
            ->leftJoin('s.theme', 't')
            ->leftJoin('s.coach', 'c')
            ->leftJoin('s.sportifs', 'sp')
            ->leftJoin('s.exercices', 'e');

        // Application des filtres
        if ($typeSeance) {
            $qb->andWhere('s.typeSeance = :typeSeance')
               ->setParameter('typeSeance', $typeSeance);
        }

        if ($niveauSeance) {
            $qb->andWhere('s.niveauSeance = :niveauSeance')
               ->setParameter('niveauSeance', $niveauSeance);
        }

        if ($dateMin) {
            $qb->andWhere('s.dateHeure >= :dateMin')
               ->setParameter('dateMin', $dateMin);
        }

        if ($dateMax) {
            $qb->andWhere('s.dateHeure <= :dateMax')
               ->setParameter('dateMax', $dateMax);
        }

        if ($statut) {
            $qb->andWhere('s.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($coachId) {
            $qb->andWhere('c.id = :coachId')
               ->setParameter('coachId', $coachId);
        }

        // Tri par date par défaut
        $qb->orderBy('s.dateHeure', 'ASC');

        // Exécution de la requête
        $seances = $qb->getQuery()->getResult();

        // Transformation des données avec la méthode utilitaire
        $data = array_map([$this, 'getSeanceData'], $seances);

        return $this->json($data);
    }

    #[Route('/seances/{id}', name: 'api_seances_show', methods: ['GET'])]
    public function showSeance(Seance $seance): JsonResponse
    {
        return $this->json($this->getSeanceData($seance));
    }

    #[Route('/seances', name: 'api_seances_create', methods: ['POST'])]
    public function createSeance(Request $request, EntityManagerInterface $entityManager, CoachRepository $coachRepository): JsonResponse
    {
        // Vérification des permissions avec le voter
        $this->denyAccessUnlessGranted('CREATE', null, 'Accès refusé: seuls les coachs, responsables et admins peuvent créer des séances');

        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (!isset($data['themeSeance']) || !isset($data['dateHeure']) || !isset($data['typeSeance']) || !isset($data['niveauSeance'])) {
            return $this->json(['error' => 'Données incomplètes'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupérer le coach (soit celui qui est connecté, soit celui spécifié)
        /** @var Coach $coach */
        $coach = $this->getUser();
        if (isset($data['coach_id']) && $this->isGranted('ROLE_ADMIN')) {
            $coach = $coachRepository->find($data['coach_id']);
            if (!$coach) {
                return $this->json(['error' => 'Coach non trouvé'], JsonResponse::HTTP_NOT_FOUND);
            }
        }

        // Création de la séance
        $seance = new Seance();
        // $seance->setThemeSeance($data['themeSeance']);
        $seance->setTheme($data['theme']);
        $seance->setDateHeure(new \DateTime($data['dateHeure']));
        $seance->setTypeSeance($data['typeSeance']);
        $seance->setNiveauSeance($data['niveauSeance']);
        $seance->setCoach($coach);
        $seance->setStatut(StatutSeance::PREVUE);

        // Enregistrement en base
        $entityManager->persist($seance);
        $entityManager->flush();

        return $this->json([
            'message' => 'Séance créée avec succès',
            'id' => $seance->getId()
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/seances/{id}', name: 'api_seances_update', methods: ['PUT'])]
    public function updateSeance(Request $request, Seance $seance, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérifier les droits d'accès
        $this->denyAccessUnlessGranted('EDIT', $seance);

        $data = json_decode($request->getContent(), true);

        // Mise à jour des champs modifiables
        if (isset($data['theme'])) {
            $seance->setTheme($data['theme']);
        }

        if (isset($data['dateHeure'])) {
            $seance->setDateHeure(new \DateTime($data['dateHeure']));
        }

        if (isset($data['typeSeance'])) {
            $seance->setTypeSeance($data['typeSeance']);
        }

        if (isset($data['niveauSeance'])) {
            $seance->setNiveauSeance($data['niveauSeance']);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Séance mise à jour avec succès'
        ]);
    }

    #[Route('/seances/{id}/statut', name: 'api_seances_update_statut', methods: ['PATCH'])]
    public function updateStatut(Request $request, Seance $seance, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérifier les droits d'accès
        $this->denyAccessUnlessGranted('UPDATE_STATUT', $seance, 'Accès refusé: vous n\'êtes pas autorisé à modifier le statut de cette séance');

        $data = json_decode($request->getContent(), true);

        if (!isset($data['statut'])) {
            return $this->json(['error' => 'Statut non spécifié'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $seance->setStatut(StatutSeance::from($data['statut']));
            $entityManager->flush();

            return $this->json([
                'message' => 'Statut mis à jour avec succès'
            ]);
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Statut invalide'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/seances/disponibles', name: 'api_seances_disponibles', methods: ['GET'])]
    public function getSeancesDisponibles(SeanceRepository $seanceRepository, Request $request): JsonResponse
    {
        $dateDebut = $request->query->get('date_debut') ? new \DateTime($request->query->get('date_debut')) : new \DateTime();
        $dateFin = $request->query->get('date_fin') ? new \DateTime($request->query->get('date_fin')) : (new \DateTime())->modify('+7 days');
        $coachId = $request->query->get('coach_id');

        // Construction de la requête
        $qb = $seanceRepository->createQueryBuilder('s')
            ->where('s.dateHeure BETWEEN :debut AND :fin')
            ->andWhere('s.statut = :statut')
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->setParameter('statut', StatutSeance::PREVUE)
            ->orderBy('s.dateHeure', 'ASC');

        // Filtrer par coach si demandé
        if ($coachId) {
            $qb->andWhere('s.coach = :coach')
                ->setParameter('coach', $coachId);
        }

        $seances = $qb->getQuery()->getResult();

        // Capacité maximale des séances (à adapter selon vos besoins)
        $capaciteMaxSeance = 10;

        $result = [];
        foreach ($seances as $seance) {
            // Vérifier s'il y a des places disponibles
            $placesDisponibles = $capaciteMaxSeance - $seance->getSportifs()->count();

            if ($placesDisponibles > 0) {
                $result[] = [
                    'id' => $seance->getId(),
                    'themeSeance' => $seance->getThemeSeance(),
                    'dateHeure' => $seance->getDateHeure()->format('Y-m-d H:i:s'),
                    'typeSeance' => $seance->getTypeSeance()->name,
                    'niveauSeance' => $seance->getNiveauSeance()->name,
                    'coach' => [
                        'id' => $seance->getCoach()->getId(),
                        'nom' => $seance->getCoach()->getNom(),
                        'prenom' => $seance->getCoach()->getPrenom(),
                    ],
                    'placesDisponibles' => $placesDisponibles,
                    'placesOccupees' => $seance->getSportifs()->count(),
                    'capaciteMax' => $capaciteMaxSeance
                ];
            }
        }

        // Si aucune séance disponible, renvoyer un message explicite
        if (empty($result)) {
            return $this->json([
                'message' => 'Aucune séance disponible pour les critères spécifiés',
                'periode' => [
                    'debut' => $dateDebut->format('Y-m-d'),
                    'fin' => $dateFin->format('Y-m-d')
                ]
            ]);
        }

        return $this->json($result);
    }

    #[Route('/seances/themes', name: 'api_seances_themes', methods: ['GET'])]
    public function getThemesSeances(SeanceRepository $seanceRepository): JsonResponse
    {
        // Récupérer les thèmes distincts des séances
        $themes = $seanceRepository->createQueryBuilder('s')
            ->select('DISTINCT s.themeSeance')
            ->getQuery()
            ->getResult();

        // Transformer le résultat
        $themesList = array_map(function ($item) {
            return $item['themeSeance'];
        }, $themes);

        return $this->json($themesList);
    }

    #[Route('/seances/{id}/exercices', name: 'api_seances_exercices', methods: ['GET'])]
    public function getSeanceExercices(Seance $seance): JsonResponse
    {
        $exercices = [];

        foreach ($seance->getExercices() as $exercice) {
            $exercices[] = [
                'id' => $exercice->getId(),
                'nom' => $exercice->getNom(),
                'description' => $exercice->getDescription(),
                // Autres propriétés de l'exercice
            ];
        }

        return $this->json($exercices);
    }

    /**
     * Endpoint combiné pour les disponibilités (créneaux et séances)
     */
    #[Route('/seances/planning-disponibilites', methods: ['GET'])]
    public function getPlanningDisponibilites(
        Request $request,
        SeanceRepository $seanceRepository,
        CoachRepository $coachRepository
    ): JsonResponse {
        $dateDebut = new \DateTime($request->query->get('date_debut', 'now'));
        $dateFin = new \DateTime($request->query->get('date_fin', '+7 days'));
        $coachId = $request->query->get('coach_id');

        // 1. Récupérer les créneaux disponibles
        $creneauxDisponibles = [];

        if ($coachId) {
            $coach = $coachRepository->find($coachId);
            if ($coach) {
                $seancesExistantes = $seanceRepository->findByCoachAndDateRange($coach, $dateDebut, $dateFin);
                $creneauxDisponibles = $this->calculerCreneauxDisponibles($dateDebut, $dateFin, $seancesExistantes);
            }
        } else {
            $tousLesCoaches = $coachRepository->findAll();
            $toutesSeances = [];

            foreach ($tousLesCoaches as $coach) {
                $seancesCoach = $seanceRepository->findByCoachAndDateRange($coach, $dateDebut, $dateFin);
                $toutesSeances = array_merge($toutesSeances, $seancesCoach);
            }

            $creneauxDisponibles = $this->calculerCreneauxDisponibles($dateDebut, $dateFin, $toutesSeances);
        }

        // 2. Récupérer les séances avec places disponibles
        // Définir la capacité maximale des séances
        $capaciteMaxSeance = 10;

        $qb = $seanceRepository->createQueryBuilder('s')
            ->where('s.dateHeure BETWEEN :debut AND :fin')
            ->andWhere('s.statut = :statut')
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->setParameter('statut', StatutSeance::PREVUE)
            ->orderBy('s.dateHeure', 'ASC');

        if ($coachId) {
            $qb->andWhere('s.coach = :coach')
                ->setParameter('coach', $coachId);
        }

        $seances = $qb->getQuery()->getResult();

        $seancesDisponibles = [];
        foreach ($seances as $seance) {
            // Calculer les places disponibles pour chaque séance
            $placesDisponibles = $capaciteMaxSeance - $seance->getSportifs()->count();

            if ($placesDisponibles > 0) {
                $seancesDisponibles[] = [
                    'id' => $seance->getId(),
                    'themeSeance' => $seance->getThemeSeance(),
                    'dateHeure' => $seance->getDateHeure()->format('Y-m-d H:i:s'),
                    'typeSeance' => $seance->getTypeSeance()->name,
                    'niveauSeance' => $seance->getNiveauSeance()->name,
                    'coach' => [
                        'id' => $seance->getCoach()->getId(),
                        'nom' => $seance->getCoach()->getNom(),
                        'prenom' => $seance->getCoach()->getPrenom(),
                    ],
                    'placesDisponibles' => $placesDisponibles,
                ];
            }
        }

        // 3. Formater la réponse combinée
        $creneauxParJour = [];

        foreach ($creneauxDisponibles as $creneau) {
            $date = substr($creneau['debut'], 0, 10); // Extraire la date (YYYY-MM-DD)
            $heure = substr($creneau['debut'], 11, 5); // Extraire l'heure (HH:MM)

            if (!isset($creneauxParJour[$date])) {
                $creneauxParJour[$date] = [
                    'date' => $date,
                    'creneaux' => []
                ];
            }

            $creneauxParJour[$date]['creneaux'][] = $heure;
        }

        return $this->json([
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d')
            ],
            'creneaux_disponibles' => array_values($creneauxParJour),
            'seances_disponibles' => $seancesDisponibles,
            'filtre_coach' => $coachId ? true : false
        ]);
    }
}
