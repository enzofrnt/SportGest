<?php

namespace App\Repository;

use App\Entity\Coach;
use App\Entity\FicheDePaie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FicheDePaie>
 *
 * @method FicheDePaie|null find($id, $lockMode = null, $lockVersion = null)
 * @method FicheDePaie|null findOneBy(array $criteria, array $orderBy = null)
 * @method FicheDePaie[]    findAll()
 * @method FicheDePaie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FicheDePaieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FicheDePaie::class);
    }

    //    /**
    //     * @return FicheDePaie[] Returns an array of FicheDePaie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FicheDePaie
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function countFichesPaieEnAttente(Coach $coach): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.coach = :coach')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findDernieresFichesPaie(Coach $coach, int $limit = 5): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.coach = :coach')
            ->setParameter('coach', $coach)
            ->orderBy('f.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findFichesPaieEnAttente(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDernieresFichesPaieGlobales(int $limit = 5): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
