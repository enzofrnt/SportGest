<?php

namespace App\Repository;

use App\Entity\Seance;
use App\Entity\Coach;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Seance>
 */
class SeanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seance::class);
    }

    //    /**
    //     * @return Seance[] Returns an array of Seance objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Seance
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function countSeancesDuJour(Coach $coach): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s)')
            ->where('s.coach = :coach')
            ->andWhere('s.dateHeure >= :debutJournee')
            ->andWhere('s.dateHeure < :finJournee')
            ->setParameter('coach', $coach)
            ->setParameter('debutJournee', new \DateTime('today'))
            ->setParameter('finJournee', new \DateTime('tomorrow'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countSportifs(Coach $coach): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT sp.id)')
            ->join('s.sportifs', 'sp')
            ->where('s.coach = :coach')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countExercices(Coach $coach): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT e.id)')
            ->join('s.exercices', 'e')
            ->where('s.coach = :coach')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function calculateRevenusMois(Coach $coach): float
    {
        $result = $this->createQueryBuilder('s')
            ->select('COUNT(s) as nb_seances')
            ->where('s.coach = :coach')
            ->andWhere('s.dateHeure >= :debutMois')
            ->andWhere('s.dateHeure < :finMois')
            ->setParameter('coach', $coach)
            ->setParameter('debutMois', new \DateTime('first day of this month'))
            ->setParameter('finMois', new \DateTime('first day of next month'))
            ->getQuery()
            ->getSingleResult();

        return $result['nb_seances'] * $coach->getTarifHoraire();
    }

    public function getSeancesParMois(Coach $coach): array
    {
        $result = $this->createQueryBuilder('s')
            ->select('s.dateHeure')
            ->where('s.coach = :coach')
            ->andWhere('s.dateHeure >= :debutAnnee')
            ->andWhere('s.dateHeure < :finAnnee')
            ->setParameter('coach', $coach)
            ->setParameter('debutAnnee', new \DateTime('first day of January'))
            ->setParameter('finAnnee', new \DateTime('first day of next year'))
            ->getQuery()
            ->getResult();

        $seancesParMois = [];
        foreach ($result as $seance) {
            $mois = (int)$seance['dateHeure']->format('n');
            if (!isset($seancesParMois[$mois])) {
                $seancesParMois[$mois] = 0;
            }
            $seancesParMois[$mois]++;
        }

        ksort($seancesParMois);

        return array_map(function ($mois, $count) {
            return [
                'mois' => $mois,
                'count' => $count
            ];
        }, array_keys($seancesParMois), array_values($seancesParMois));
    }

    public function findProchainesSeances(Coach $coach): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.coach = :coach')
            ->andWhere('s.dateHeure >= :now')
            ->andWhere('s.statut = :statut')
            ->orderBy('s.dateHeure', 'ASC')
            ->setMaxResults(5)
            ->setParameter('coach', $coach)
            ->setParameter('now', new \DateTime())
            ->setParameter('statut', 'prévue')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les séances d'un coach dans une plage de dates
     */
    public function findByCoachAndDateRange(Coach $coach, \DateTime $dateDebut, \DateTime $dateFin)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.coach = :coach')
            ->andWhere('s.dateHeure >= :dateDebut')
            ->andWhere('s.dateHeure <= :dateFin')
            ->setParameter('coach', $coach)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de séances dans une plage de dates
     */
    public function countSeancesByDateRange(\DateTime $dateDebut, \DateTime $dateFin)
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.dateHeure >= :dateDebut')
            ->andWhere('s.dateHeure <= :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre total de participants dans une plage de dates
     */
    public function countTotalParticipantsByDateRange(\DateTime $dateDebut, \DateTime $dateFin)
    {
        return $this->createQueryBuilder('s')
            ->select('SUM(SIZE(s.sportifs))')
            ->andWhere('s.dateHeure >= :dateDebut')
            ->andWhere('s.dateHeure <= :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Obtient des statistiques par type de séance
     */
    public function getStatsByTypeSeance(\DateTime $dateDebut, \DateTime $dateFin)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery(
            'SELECT 
                s.typeSeance as type,
                COUNT(s.id) as nb_seances,
                SUM(SIZE(s.sportifs)) as nb_participants
             FROM App\Entity\Seance s
             WHERE s.dateHeure >= :dateDebut AND s.dateHeure <= :dateFin
             GROUP BY s.typeSeance'
        )->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        return $query->getResult();
    }

    /**
     * Obtient des statistiques par coach
     */
    public function getStatsByCoach(\DateTime $dateDebut, \DateTime $dateFin)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery(
            'SELECT 
                CONCAT(c.nom, \' \', c.prenom) as coach_name,
                COUNT(s.id) as nb_seances,
                SUM(SIZE(s.sportifs)) as nb_participants
             FROM App\Entity\Seance s
             JOIN s.coach c
             WHERE s.dateHeure >= :dateDebut AND s.dateHeure <= :dateFin
             GROUP BY c.id'
        )->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        return $query->getResult();
    }
}