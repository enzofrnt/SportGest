<?php

namespace App\Controller\Dashboard;

use App\Entity\Coach;
use App\Entity\Exercice;
use App\Entity\FicheDePaie;
use App\Entity\Responsable;
use App\Entity\Seance;
use App\Entity\Sportif;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Repository\SeanceRepository;
use App\Repository\FicheDePaieRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;

#[AdminDashboard(
    routePath: '/admin',
    routeName: 'admin'
)]
class DashboardController extends AbstractDashboardController
{
    private EntityManagerInterface $entityManager;
    private SeanceRepository $seanceRepository;
    private FicheDePaieRepository $ficheDePaieRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SeanceRepository $seanceRepository,
        FicheDePaieRepository $ficheDePaieRepository
    ) {
        $this->entityManager = $entityManager;
        $this->seanceRepository = $seanceRepository;
        $this->ficheDePaieRepository = $ficheDePaieRepository;
    }

    public function index(): Response
    {
        $user = $this->getUser();

        if (!($user instanceof Coach || $user instanceof Responsable)) {
            throw new AccessDeniedException('Accès non autorisé');
        }

        if ($user instanceof Coach) {
            return $this->renderCoachDashboard($user);
        }

        if ($user instanceof Responsable) {
            return $this->renderResponsableDashboard($user);
        }

        throw new AccessDeniedException('Type d\'utilisateur non reconnu');
    }

    private function renderCoachDashboard(Coach $coach): Response
    {
        // Récupérer les statistiques
        $stats = [
            'seances_mois' => $this->seanceRepository->countSeancesMoisCourant($coach),
            'sportifs' => $this->seanceRepository->countSportifsUniques($coach),
            'fiches_paie_attente' => $this->ficheDePaieRepository->countFichesPaieEnAttente($coach),
        ];

        // Récupérer les prochaines séances
        $prochaines_seances = $this->seanceRepository->findProchainesSeances($coach, 5);

        // Récupérer les dernières fiches de paie
        $fiches_paie = $this->ficheDePaieRepository->findDernieresFichesPaie($coach, 5);

        return $this->render('dashboard/coach_dashboard.html.twig', [
            'stats' => $stats,
            'prochaines_seances' => $prochaines_seances,
            'fiches_paie' => $fiches_paie,
        ]);
    }

    private function renderResponsableDashboard(Responsable $responsable): Response
    {
        // Récupérer les statistiques
        $coachs = $this->entityManager->getRepository(Coach::class)->findAll();
        $sportifs = $this->entityManager->getRepository(Sportif::class)->findAll();
        $seances_mois = [];
        foreach ($coachs as $coach) {
            $seances_mois[] = $this->seanceRepository->countSeancesMoisCourant($coach);
        }

        // Récupérer les dernières séances
        // $dernieres_seances = $this->seanceRepository->findBy([], ['dateHeure' => 'DESC'], 5);

        // Récupérer les dernières fiches de paie
        // $dernieres_fiches_paie = $this->ficheDePaieRepository->findBy([], ['date' => 'DESC'], 5);

        return $this->render('dashboard/responsable_dashboard.html.twig', [
            'user' => $responsable,
            'coachs' => $coachs,
            'sportifs' => $sportifs,
            'seances_mois' => $seances_mois,
            // 'dernieres_seances' => $dernieres_seances,
            // 'dernieres_fiches_paie' => $dernieres_fiches_paie,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SportGest')
            ->setFaviconPath('favicon.svg')
            ->renderContentMaximized()
            ->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        $user = $this->getUser();

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        if ($user instanceof Coach) {
            yield MenuItem::section('Gestion des séances');
            yield MenuItem::linkToCrud('Mes séances', 'fas fa-calendar-alt', Seance::class)
                ->setDefaultSort(['dateHeure' => 'DESC']);
            yield MenuItem::linkToCrud('Exercices', 'fas fa-dumbbell', Exercice::class);
            yield MenuItem::linkToCrud('Mes fiches de paie', 'fas fa-file-invoice-dollar', FicheDePaie::class);
        }

        if ($user instanceof Responsable) {
            if ($this->isGranted('ROLE_ADMIN')) {
                yield MenuItem::section('Administration');
                yield MenuItem::linkToCrud('Responsables', 'fas fa-user-tie', Responsable::class);
                yield MenuItem::linkToCrud('Coachs', 'fas fa-user-friends', Coach::class);
            }
            
            yield MenuItem::section('Gestion');
            yield MenuItem::linkToCrud('Toutes les séances', 'fas fa-calendar-alt', Seance::class)
                ->setDefaultSort(['dateHeure' => 'DESC']);
            yield MenuItem::linkToCrud('Fiches de paie', 'fas fa-file-invoice-dollar', FicheDePaie::class);
        }
    }
}
