<?php

namespace App\Controller\Coach;

use App\Entity\Exercice;
use App\Entity\Seance;
use App\Entity\Coach;
use App\Repository\SeanceRepository;
use App\Repository\UtilisateurRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Controller\Crud\ExerciceCrudController;
use App\Controller\Crud\SeanceCrudController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FicheDePaie;
use App\Entity\Utilisateur;
use App\Enum\PeriodePaie;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[IsGranted('ROLE_COACH')]
#[Route('/dashboard/coach')]
class CoachDashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SeanceRepository $seanceRepository,
        private UtilisateurRepository $utilisateurRepository
    ) {
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SportGest - Espace Coach');
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
                MenuItem::linkToRoute('Mon Profil', 'fas fa-user', 'coach_profil'),
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
        yield MenuItem::linkToDashboard('Tableau de bord', 'fas fa-home');

        yield MenuItem::section('Gestion');
        yield MenuItem::linkToCrud('Exercices', 'fas fa-dumbbell', Exercice::class)
            ->setController(ExerciceCrudController::class);
        yield MenuItem::linkToCrud('Séances', 'fas fa-calendar-alt', Seance::class)
            ->setController(SeanceCrudController::class);

        yield MenuItem::section('Statistiques');
        yield MenuItem::linkToRoute('Statistiques des séances', 'fas fa-chart-bar', 'coach_seance_stats');
        yield MenuItem::linkToRoute('Statistiques des utilisateurs', 'fas fa-users', 'coach_utilisateur_stats');
    }

    #[Route('', name: 'coach_dashboard')]
    public function index(): Response
    {
        $coach = $this->getUser();
        
        if (!$coach instanceof Coach) {
            throw new AccessDeniedHttpException('Accès réservé aux coachs');
        }

        // Statistiques générales
        $stats = [
            'total_seances' => $this->seanceRepository->count(['coach' => $coach]),
            'seances_du_jour' => $this->seanceRepository->countSeancesDuJour($coach),
            'total_sportifs' => $this->seanceRepository->countSportifs($coach),
            'total_exercices' => $this->seanceRepository->countExercices($coach),
            'revenus_mois' => $this->seanceRepository->calculateRevenusMois($coach),
        ];

        // Statistiques des séances par mois
        $seancesParMois = $this->seanceRepository->getSeancesParMois($coach);
        $stats['seances_par_mois'] = [
            'labels' => array_map(fn($item) => date('F', mktime(0, 0, 0, $item['mois'], 1)), $seancesParMois),
            'data' => array_map(fn($item) => $item['count'], $seancesParMois),
        ];

        // Prochaines séances
        $prochaines_seances = $this->seanceRepository->findProchainesSeances($coach);

        // Dernières fiches de paie
        $derniere_fiches = $this->entityManager->getRepository(FicheDePaie::class)
            ->findBy(
                ['coach' => $coach, 'periode' => PeriodePaie::MOIS],
                ['id' => 'DESC'],
                3
            );

        return $this->render('coach/dashboard.html.twig', [
            'stats' => $stats,
            'prochaines_seances' => $prochaines_seances,
            'derniere_fiches' => $derniere_fiches,
        ]);
    }
} 