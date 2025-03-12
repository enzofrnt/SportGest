<?php

namespace App\Controller\Coach;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Exercice;
use App\Entity\Seance;
use App\Entity\Coach;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FicheDePaie;
use App\Enum\PeriodePaie;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AdminDashboard(routePath: '/coach', routeName: 'coach_dashboard')]
#[IsGranted('ROLE_COACH')]
class CoachDashboardController extends AbstractDashboardController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/coach', name: 'coach_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$user instanceof Coach) {
            throw new AccessDeniedHttpException('Accès réservé aux coachs');
        }

        $coach = $user;

        // Statistiques générales
        $stats = [
            'total_seances' => $this->entityManager->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('COUNT(s)')
                ->where('s.coach = :coach')
                ->setParameter('coach', $coach)
                ->getQuery()
                ->getSingleScalarResult(),
            'seances_du_jour' => $this->entityManager->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('COUNT(s)')
                ->where('s.coach = :coach')
                ->andWhere('s.dateHeure >= :debutJournee')
                ->andWhere('s.dateHeure < :finJournee')
                ->setParameter('coach', $coach)
                ->setParameter('debutJournee', new \DateTime('today'))
                ->setParameter('finJournee', new \DateTime('tomorrow'))
                ->getQuery()
                ->getSingleScalarResult(),
            'total_sportifs' => $this->entityManager->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('COUNT(DISTINCT sportif) as count')
                ->join('s.sportifs', 'sportif')
                ->where('s.coach = :coach')
                ->setParameter('coach', $coach)
                ->getQuery()
                ->getSingleScalarResult(),
            'total_exercices' => $this->entityManager->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('COUNT(DISTINCT e.id)')
                ->join('s.exercices', 'e')
                ->where('s.coach = :coach')
                ->setParameter('coach', $coach)
                ->getQuery()
                ->getSingleScalarResult(),
            'revenus_mois' => $this->entityManager->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('COUNT(s) * :tarif as revenus')
                ->where('s.coach = :coach')
                ->andWhere('s.dateHeure >= :debutMois')
                ->andWhere('s.dateHeure < :debutMoisSuivant')
                ->setParameter('coach', $coach)
                ->setParameter('tarif', $coach->getTarifHoraire())
                ->setParameter('debutMois', new \DateTime('first day of this month'))
                ->setParameter('debutMoisSuivant', new \DateTime('first day of next month'))
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        // Statistiques des séances par mois
        $seancesParMois = $this->entityManager->getRepository(Seance::class)
            ->createQueryBuilder('s')
            ->select('SUBSTRING(s.dateHeure, 6, 2) as mois, COUNT(s) as count')
            ->where('s.coach = :coach')
            ->andWhere('SUBSTRING(s.dateHeure, 1, 4) = :annee')
            ->groupBy('mois')
            ->setParameter('coach', $coach)
            ->setParameter('annee', date('Y'))
            ->getQuery()
            ->getResult();

        $stats['seances_par_mois'] = [
            'labels' => array_map(fn($item) => date('F', mktime(0, 0, 0, $item['mois'], 1)), $seancesParMois),
            'data' => array_map(fn($item) => $item['count'], $seancesParMois),
        ];

        // Prochaines séances
        $prochaines_seances = $this->entityManager->getRepository(Seance::class)
            ->createQueryBuilder('s')
            ->where('s.coach = :coach')
            ->andWhere('s.dateHeure >= CURRENT_DATE()')
            ->orderBy('s.dateHeure', 'ASC')
            ->setMaxResults(5)
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getResult();

        // Dernières fiches de paie
        $derniere_fiches = $this->entityManager->getRepository(FicheDePaie::class)
            ->createQueryBuilder('f')
            ->where('f.coach = :coach')
            ->andWhere('f.periode = :periode')
            ->orderBy('f.id', 'DESC')
            ->setMaxResults(3)
            ->setParameter('coach', $coach)
            ->setParameter('periode', PeriodePaie::MOIS)
            ->getQuery()
            ->getResult();

        return $this->render('coach/dashboard.html.twig', [
            'stats' => $stats,
            'prochaines_seances' => $prochaines_seances,
            'derniere_fiches' => $derniere_fiches,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SportGest - Espace Coach');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        yield MenuItem::section('Gestion');
        yield MenuItem::linkToCrud('Exercices', 'fas fa-dumbbell', Exercice::class);
        yield MenuItem::linkToCrud('Séances', 'fas fa-calendar-alt', Seance::class);
    }
} 