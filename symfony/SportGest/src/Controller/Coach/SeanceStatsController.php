<?php

namespace App\Controller\Coach;

use App\Entity\Coach;
use App\Repository\SeanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[IsGranted('ROLE_COACH')]
#[Route('/dashboard/coach')]
class SeanceStatsController extends AbstractController
{
    public function __construct(
        private SeanceRepository $seanceRepository
    ) {
    }

    #[Route('/seance-stats', name: 'coach_seance_stats')]
    public function index(): Response
    {
        $coach = $this->getUser();
        
        if (!$coach instanceof Coach) {
            throw new AccessDeniedHttpException('Accès réservé aux coachs');
        }

        // Statistiques des séances par mois
        $seancesParMois = $this->seanceRepository->getSeancesParMois($coach);
        
        // Statistiques des séances par type
        $seancesParType = $this->seanceRepository->createQueryBuilder('s')
            ->select('s.typeSeance as type, COUNT(s) as count')
            ->where('s.coach = :coach')
            ->groupBy('s.typeSeance')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getResult();

        // Statistiques des séances par statut
        $seancesParStatut = $this->seanceRepository->createQueryBuilder('s')
            ->select('s.statut as status, COUNT(s) as count')
            ->where('s.coach = :coach')
            ->groupBy('s.statut')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getResult();

        // Statistiques des séances par niveau
        $seancesParNiveau = $this->seanceRepository->createQueryBuilder('s')
            ->select('s.niveauSeance as niveau, COUNT(s) as count')
            ->where('s.coach = :coach')
            ->groupBy('s.niveauSeance')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getResult();

        return $this->render('coach/seance_stats.html.twig', [
            'seancesParMois' => [
                'labels' => array_map(fn($item) => date('F', mktime(0, 0, 0, $item['mois'], 1)), $seancesParMois),
                'data' => array_map(fn($item) => $item['count'], $seancesParMois),
            ],
            'seancesParType' => [
                'labels' => array_map(fn($item) => $item['type']->value, $seancesParType),
                'data' => array_map(fn($item) => $item['count'], $seancesParType),
            ],
            'seancesParStatut' => [
                'labels' => array_map(fn($item) => $item['status']->value, $seancesParStatut),
                'data' => array_map(fn($item) => $item['count'], $seancesParStatut),
            ],
            'seancesParNiveau' => [
                'labels' => array_map(fn($item) => $item['niveau']->value, $seancesParNiveau),
                'data' => array_map(fn($item) => $item['count'], $seancesParNiveau),
            ],
        ]);
    }
} 