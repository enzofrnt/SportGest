<?php

namespace App\Repository;

use App\Entity\Seance;
use App\Entity\Coach;
use App\Entity\Sportif;
use App\Enum\StatutSeance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Seance>
 *
 * @method Seance|null find($id, $lockMode = null, $lockVersion = null)
 * @method Seance|null findOneBy(array $criteria, array $orderBy = null)
 * @method Seance[]    findAll()
 * @method Seance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
            ->select('COUNT(DISTINCT r.sportif)')
            ->join('s.reservations', 'r')
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

    public function findProchainesSeances(Coach $coach, int $limit = 5): array
    {
        $maintenant = new \DateTime();

        return $this->createQueryBuilder('s')
            ->where('s.coach = :coach')
            ->andWhere('s.dateHeure > :maintenant')
            ->setParameter('coach', $coach)
            ->setParameter('maintenant', $maintenant)
            ->orderBy('s.dateHeure', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findDernieresSeances(int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.dateHeure', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findSeancesMois(\DateTime $debut, \DateTime $fin): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.dateHeure BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('s.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countSeancesMoisCourant(Coach $coach): int
    {
        $debutMois = new \DateTime('first day of this month');
        $debutMois->setTime(0, 0, 0);
        $finMois = new \DateTime('last day of this month');
        $finMois->setTime(23, 59, 59);

        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.coach = :coach')
            ->andWhere('s.dateHeure BETWEEN :debut AND :fin')
            ->setParameter('coach', $coach)
            ->setParameter('debut', $debutMois)
            ->setParameter('fin', $finMois)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countSportifsUniques(Coach $coach): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT r.sportif)')
            ->join('s.reservations', 'r')
            ->where('s.coach = :coach')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findSeancesBySportif(Sportif $sportif): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.reservations', 'r')
            ->where('r.sportif = :sportifId')
            ->setParameter('sportifId', $sportif->getId())
            ->orderBy('s.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSeancesTermineesBySportif(Sportif $sportif): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.reservations', 'r')
            ->where('r.sportif = :sportifId')
            ->andWhere('s.statut = :statut')
            ->setParameter('sportifId', $sportif->getId())
            ->setParameter('statut', StatutSeance::VALIDEE)
            ->orderBy('s.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSeancesValideesBySportifAndDates(Sportif $sportif, \DateTime $dateMin, \DateTime $dateMax): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.reservations', 'r')
            ->where('r.sportif = :sportifId')
            ->andWhere('s.statut = :statut')
            ->andWhere('s.dateHeure BETWEEN :dateMin AND :dateMax')
            ->setParameter('sportifId', $sportif->getId())
            ->setParameter('statut', StatutSeance::VALIDEE)
            ->setParameter('dateMin', $dateMin)
            ->setParameter('dateMax', $dateMax)
            ->orderBy('s.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function countSeancesByDateRange(\DateTime $dateDebut, \DateTime $dateFin): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.dateHeure BETWEEN :debut AND :fin')
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countParticipationsByDateRange(\DateTime $dateDebut, \DateTime $dateFin): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(r.id)')
            ->join('s.reservations', 'r')
            ->where('s.dateHeure BETWEEN :debut AND :fin')
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getDistributionByDayOfWeek(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT DAYOFWEEK(s.date_heure) as jour, COUNT(s.id) as nombre
            FROM seance s
            WHERE s.date_heure BETWEEN :debut AND :fin
            GROUP BY DAYOFWEEK(s.date_heure)
            ORDER BY jour ASC
        ';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery([
            'debut' => $dateDebut->format('Y-m-d H:i:s'),
            'fin' => $dateFin->format('Y-m-d H:i:s'),
        ]);

        $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

        // Initialiser avec 0 pour tous les jours
        $result = [];
        foreach ($jours as $index => $nom) {
            $result[] = [
                'jour' => $nom,
                'jour_index' => $index + 1,
                'nombre' => 0
            ];
        }

        // Remplir avec les valeurs réelles
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $jourIndex = $row['jour'] - 1;
            if (isset($result[$jourIndex])) {
                $result[$jourIndex]['nombre'] = $row['nombre'];
            }
        }

        return $result;
    }

    public function getDistributionByHourOfDay(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT HOUR(s.date_heure) as heure, COUNT(s.id) as nombre
            FROM seance s
            WHERE s.date_heure BETWEEN :debut AND :fin
            GROUP BY HOUR(s.date_heure)
            ORDER BY heure ASC
        ';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery([
            'debut' => $dateDebut->format('Y-m-d H:i:s'),
            'fin' => $dateFin->format('Y-m-d H:i:s'),
        ]);

        // Initialiser avec 0 pour toutes les heures de 6h à 22h
        $result = [];
        for ($i = 6; $i <= 22; $i++) {
            $result[] = [
                'heure' => $i,
                'nombre' => 0
            ];
        }

        // Remplir avec les valeurs réelles
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $heure = $row['heure'];
            if ($heure >= 6 && $heure <= 22) {
                $index = $heure - 6;
                $result[$index]['nombre'] = $row['nombre'];
            }
        }

        return $result;
    }

    public function findSeancesByCoachAndDateRange(Coach $coach, \DateTime $dateDebut, \DateTime $dateFin): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.coach = :coach')
            ->andWhere('s.dateHeure BETWEEN :debut AND :fin')
            ->setParameter('coach', $coach)
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->orderBy('s.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countSeancesByCoachAndDateRange(Coach $coach, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.coach = :coach')
            ->andWhere('s.dateHeure BETWEEN :debut AND :fin')
            ->setParameter('coach', $coach)
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countParticipantsByCoachAndDateRange(Coach $coach, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(sp.id)')
            ->join('s.sportifs', 'sp')
            ->where('s.coach = :coach')
            ->andWhere('s.dateHeure BETWEEN :debut AND :fin')
            ->setParameter('coach', $coach)
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getWeeklyEvolution(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Requête pour obtenir le nombre de séances par semaine
        $sql = '
            SELECT WEEK(s.date_heure) as semaine, 
                   YEAR(s.date_heure) as annee,
                   MIN(s.date_heure) as debut_semaine,
                   COUNT(s.id) as nb_seances,
                   COUNT(DISTINCT r.sportif_id) as nb_participants
            FROM seance s
            LEFT JOIN reservation r ON s.id = r.seance_id
            WHERE s.date_heure BETWEEN :debut AND :fin
            GROUP BY YEAR(s.date_heure), WEEK(s.date_heure)
            ORDER BY annee ASC, semaine ASC
        ';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery([
            'debut' => $dateDebut->format('Y-m-d H:i:s'),
            'fin' => $dateFin->format('Y-m-d H:i:s'),
        ]);

        $results = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $debutSemaine = new \DateTime($row['debut_semaine']);
            $results[] = [
                'semaine' => $row['semaine'],
                'annee' => $row['annee'],
                'debut_semaine' => $debutSemaine->format('Y-m-d'),
                'label_semaine' => 'S' . $row['semaine'] . ' ' . $row['annee'],
                'nb_seances' => $row['nb_seances'],
                'nb_participants' => $row['nb_participants'],
                'moyenne_participants' => $row['nb_seances'] > 0 ? round($row['nb_participants'] / $row['nb_seances'], 2) : 0
            ];
        }

        return $results;
    }

    /**
     * Trouve toutes les séances validées pour un coach.
     */
    public function findValidatedSeancesByCoach(Coach $coach): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.coach = :coach')
            ->andWhere('s.statut = :statut')
            ->setParameter('coach', $coach)
            ->setParameter('statut', StatutSeance::VALIDEE)
            ->orderBy('s.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }
}