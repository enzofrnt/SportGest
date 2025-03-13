<?php

namespace App\Controller\Coach;

use App\Entity\Coach;
use App\Repository\SeanceRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[IsGranted('ROLE_COACH')]
#[Route('/dashboard/coach')]
class UtilisateurStatsController extends AbstractController
{
    public function __construct(
        private SeanceRepository $seanceRepository,
        private UtilisateurRepository $utilisateurRepository
    ) {
    }

    #[Route('/utilisateur-stats', name: 'coach_utilisateur_stats')]
    public function index(): Response
    {
        $coach = $this->getUser();
        
        if (!$coach instanceof Coach) {
            throw new AccessDeniedHttpException('Accès réservé aux coachs');
        }

        // Nombre total de sportifs
        $totalSportifs = $this->seanceRepository->countSportifs($coach);

        // Sportifs par niveau
        $sportifsParNiveau = $this->seanceRepository->createQueryBuilder('s')
            ->select('sp.niveauSportif as niveau, COUNT(DISTINCT sp.id) as count')
            ->join('s.sportifs', 'sp')
            ->where('s.coach = :coach')
            ->groupBy('sp.niveauSportif')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getResult();

        // Top 5 des sportifs les plus actifs
        $topSportifs = $this->seanceRepository->createQueryBuilder('s')
            ->select('sp.id', 'sp.nom', 'sp.prenom', 'COUNT(s) as nb_seances')
            ->join('s.sportifs', 'sp')
            ->where('s.coach = :coach')
            ->groupBy('sp.id')
            ->orderBy('nb_seances', 'DESC')
            ->setMaxResults(5)
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getResult();

        // Répartition des séances par type pour chaque sportif
        $seancesParTypeParSportif = $this->seanceRepository->createQueryBuilder('s')
            ->select('sp.id', 'sp.nom', 'sp.prenom', 's.typeSeance as type', 'COUNT(s) as count')
            ->join('s.sportifs', 'sp')
            ->where('s.coach = :coach')
            ->groupBy('sp.id', 's.typeSeance')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getResult();

        // Organiser les données pour le graphique
        $seancesParTypeParSportifData = [];
        foreach ($seancesParTypeParSportif as $item) {
            $sportifId = $item['id'];
            if (!isset($seancesParTypeParSportifData[$sportifId])) {
                $seancesParTypeParSportifData[$sportifId] = [
                    'nom' => $item['nom'] . ' ' . $item['prenom'],
                    'types' => []
                ];
            }
            $seancesParTypeParSportifData[$sportifId]['types'][$item['type']->value] = $item['count'];
        }

        return $this->render('coach/utilisateur_stats.html.twig', [
            'totalSportifs' => $totalSportifs,
            'sportifsParNiveau' => [
                'labels' => array_map(fn($item) => $item['niveau']->value, $sportifsParNiveau),
                'data' => array_map(fn($item) => $item['count'], $sportifsParNiveau),
            ],
            'topSportifs' => $topSportifs,
            'seancesParTypeParSportif' => array_values($seancesParTypeParSportifData),
        ]);
    }
} 