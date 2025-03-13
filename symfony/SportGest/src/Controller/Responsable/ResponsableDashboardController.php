<?php

namespace App\Controller\Responsable;

use App\Entity\Exercice;
use App\Entity\Seance;
use App\Entity\Utilisateur;
use App\Entity\Coach;
use App\Entity\Responsable;
use App\Entity\FicheDePaie;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Controller\Crud\ExerciceCrudController;
use App\Controller\Crud\SeanceCrudController;
use App\Controller\Crud\UtilisateurCrudController;
use App\Controller\Crud\CoachCrudController;
use App\Controller\Crud\ResponsableCrudController;
use App\Entity\Sportif;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_RESPONSABLE')]
#[Route('/dashboard/responsable')]
class ResponsableDashboardController extends AbstractDashboardController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SportGest - Espace Responsable');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        if (!$user instanceof Utilisateur) {
            throw new \RuntimeException('L\'utilisateur doit être une instance de Utilisateur');
        }

        return parent::configureUserMenu($user)
            ->setName($user->getPrenom() . ' ' . $user->getNom())
            ->setGravatarEmail($user->getEmail())
            ->addMenuItems([
                MenuItem::linkToRoute('Mon Profil', 'fas fa-user', 'responsable_profil'),
            ]);
    }

    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addCssFile('css/admin.css')
            ->addJsFile('js/admin.js');
    }

    public function configureMenuItems(): iterable
    {
        $responsable = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        yield MenuItem::linkToDashboard('Tableau de bord', 'fas fa-home');

        yield MenuItem::section('Gestion');
        yield MenuItem::linkToCrud('Exercices', 'fas fa-dumbbell', Exercice::class)
            ->setController(ExerciceCrudController::class);
        yield MenuItem::linkToCrud('Séances', 'fas fa-calendar-alt', Seance::class)
            ->setController(SeanceCrudController::class);
        yield MenuItem::linkToCrud('Sportifs', 'fas fa-running', Utilisateur::class)
            ->setController(UtilisateurCrudController::class);
        yield MenuItem::linkToCrud('Coach', 'fas fa-user-tie', Coach::class)
            ->setController(CoachCrudController::class);

        if ($isAdmin) {
            yield MenuItem::linkToCrud('Responsables', 'fas fa-users-cog', Responsable::class)
                ->setController(ResponsableCrudController::class);
        }

        yield MenuItem::section('Statistiques');
        yield MenuItem::linkToRoute('Statistiques globales', 'fas fa-chart-line', 'responsable_stats');
    }

    #[Route('', name: 'responsable_dashboard')]
    public function index(): Response
    {
        $responsable = $this->getUser();
        
        if (!$responsable instanceof Responsable) {
            throw new AccessDeniedHttpException('Accès réservé aux responsables');
        }

        // Statistiques générales
        $stats = [
            'total_utilisateurs' => $this->entityManager->getRepository(Utilisateur::class)->count([]),
            'total_seances' => $this->entityManager->getRepository(Seance::class)->count([]),
            'total_sportifs' => $this->entityManager->getRepository(Sportif::class)->count([]),
            'total_coachs' => $this->entityManager->getRepository(Coach::class)->count([]),
            'total_exercices' => $this->entityManager->getRepository(Exercice::class)->count([]),
            'seances_du_jour' => $this->entityManager->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('COUNT(s)')
                ->where('s.dateHeure >= :debut')
                ->andWhere('s.dateHeure < :fin')
                ->setParameter('debut', new \DateTime('today'))
                ->setParameter('fin', new \DateTime('tomorrow'))
                ->getQuery()
                ->getSingleScalarResult(),
            'total_fiches_paie' => $this->entityManager->getRepository(FicheDePaie::class)->count([]),
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
            ->findBy([], ['dateHeure' => 'DESC'], 5);

        return $this->render('responsable/dashboard.html.twig', [
            'stats' => $stats,
            'derniere_seances' => $derniere_seances,
        ]);
    }
} 