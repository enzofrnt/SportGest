<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Utilisateur;
use App\Entity\Coach;
use App\Entity\Sportif;
use App\Entity\Responsable;
use App\Entity\Exercice;
use App\Entity\Seance;
use App\Entity\FicheDePaie;
use App\Enum\PeriodePaie;
use Doctrine\ORM\EntityManagerInterface;

#[AdminDashboard(routePath: '/dashboard/responsable', routeName: 'responsable')]
class ResponsableController extends AbstractDashboardController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/dashboard/responsable', name: 'responsable')]
    public function index(): Response
    {
        // Statistiques générales
        $stats = [
            'total_utilisateurs' => $this->entityManager->getRepository(Utilisateur::class)->count([]),
            'total_coachs' => $this->entityManager->getRepository(Coach::class)->count([]),
            'total_sportifs' => $this->entityManager->getRepository(Sportif::class)->count([]),
            'total_exercices' => $this->entityManager->getRepository(Exercice::class)->count([]),
            'total_seances' => $this->entityManager->getRepository(Seance::class)->count([]),
            'seances_du_jour' => $this->entityManager->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('COUNT(s)')
                ->where('s.dateHeure >= :debutJournee')
                ->andWhere('s.dateHeure < :finJournee')
                ->setParameter('debutJournee', new \DateTime('today'))
                ->setParameter('finJournee', new \DateTime('tomorrow'))
                ->getQuery()
                ->getSingleScalarResult(),
            'total_fiches_paie' => $this->entityManager->getRepository(FicheDePaie::class)
                ->createQueryBuilder('f')
                ->select('COUNT(f)')
                ->where('f.periode = :periode')
                ->setParameter('periode', PeriodePaie::MOIS)
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        // Statistiques des séances par type
        $seancesParType = $this->entityManager->getRepository(Seance::class)
            ->createQueryBuilder('s')
            ->select('s.typeSeance as type, COUNT(s) as count')
            ->groupBy('s.typeSeance')
            ->getQuery()
            ->getResult();

        $stats['seances_par_type'] = [
            'labels' => array_map(fn($item) => $item['type']->value, $seancesParType),
            'data' => array_map(fn($item) => $item['count'], $seancesParType),
        ];

        // Statistiques des statuts
        $statuts = $this->entityManager->getRepository(Seance::class)
            ->createQueryBuilder('s')
            ->select('s.statut as status, COUNT(s) as count')
            ->groupBy('s.statut')
            ->getQuery()
            ->getResult();

        $stats['statuts'] = [
            'labels' => array_map(fn($item) => $item['status']->value, $statuts),
            'data' => array_map(fn($item) => $item['count'], $statuts),
        ];

        // Dernières séances
        $derniere_seances = $this->entityManager->getRepository(Seance::class)
            ->createQueryBuilder('s')
            ->orderBy('s.dateHeure', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'derniere_seances' => $derniere_seances,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SportGest - Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', Utilisateur::class);
        yield MenuItem::linkToCrud('Coach', 'fas fa-user-tie', Coach::class);
        yield MenuItem::linkToCrud('Sportif', 'fas fa-running', Sportif::class);
        yield MenuItem::linkToCrud('Responsable', 'fas fa-user-shield', Responsable::class);
        yield MenuItem::section('Exercices et Séances');
        yield MenuItem::linkToCrud('Exercices', 'fas fa-dumbbell', Exercice::class);
        yield MenuItem::linkToCrud('Séances', 'fas fa-calendar-alt', Seance::class);
        yield MenuItem::section('Gestion financière');
        yield MenuItem::linkToCrud('Fiches de paie', 'fas fa-file-invoice-dollar', FicheDePaie::class);
    }
}
