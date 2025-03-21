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
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Reservation;
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
        $total_seances_mois = 0;
        $total_sportifs = count($sportifs);
        $total_coachs = count($coachs);
        $total_seances = $this->seanceRepository->count([]);

        // Préparer les données pour le graphique des séances par coach
        $coachs_labels = [];
        $seances_mois_values = [];

        foreach ($coachs as $coach) {
            $seances_coach = $this->seanceRepository->countSeancesMoisCourant($coach);
            $seances_mois[$coach->getId()] = $seances_coach;
            $total_seances_mois += $seances_coach;

            $coachs_labels[] = $coach->getPrenom() . ' ' . $coach->getNom();
            $seances_mois_values[] = $seances_coach;
        }

        // Récupérer les dernières séances
        $dernieres_seances = $this->seanceRepository->findBy([], ['dateHeure' => 'DESC'], 5);

        // Récupérer les dernières fiches de paie
        $dernieres_fiches_paie = $this->ficheDePaieRepository->findDernieresFichesPaieGlobales(5);

        // Statistiques globales
        $stats = [
            'total_seances_mois' => $total_seances_mois,
            'total_sportifs' => $total_sportifs,
            'total_coachs' => $total_coachs,
            'total_seances' => $total_seances,
            'seances_mois' => $seances_mois,
            'coachs_labels' => $coachs_labels,
            'seances_mois_values' => $seances_mois_values,
        ];

        // Statistiques avancées pour les administrateurs
        if ($this->isGranted('ROLE_ADMIN')) {
            // Top 5 des coachs les plus actifs
            $top_coachs = [];
            foreach ($coachs as $coach) {
                $top_coachs[] = [
                    'prenom' => $coach->getPrenom(),
                    'nom' => $coach->getNom(),
                    'seances_mois' => $this->seanceRepository->countSeancesMoisCourant($coach),
                    'sportifs' => $this->seanceRepository->countSportifsUniques($coach),
                ];
            }
            usort($top_coachs, function ($a, $b) {
                return $b['seances_mois'] <=> $a['seances_mois'];
            });
            $stats['top_coachs'] = array_slice($top_coachs, 0, 5);

            // Top 5 des séances les plus fréquentées
            $seances = $this->seanceRepository->findBy([], ['dateHeure' => 'DESC']);
            $top_seances = [];
            foreach ($seances as $seance) {
                $top_seances[] = [
                    'theme' => $seance->getTheme() ? $seance->getTheme()->getNom() : '',
                    'coach' => $seance->getCoach()->getPrenom() . ' ' . $seance->getCoach()->getNom(),
                    'sportifs' => $seance->getReservations()->count(),
                ];
            }
            usort($top_seances, function ($a, $b) {
                return $b['sportifs'] <=> $a['sportifs'];
            });
            $stats['top_seances'] = array_slice($top_seances, 0, 5);

            // Nombre de fiches de paie en attente
            $stats['fiches_paie_attente'] = count($this->ficheDePaieRepository->findFichesPaieEnAttente());
        }

        return $this->render('dashboard/responsable_dashboard.html.twig', [
            'user' => $responsable,
            'coachs' => $coachs,
            'sportifs' => $sportifs,
            'stats' => $stats,
            'dernieres_seances' => $dernieres_seances,
            'dernieres_fiches_paie' => $dernieres_fiches_paie,
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

        yield MenuItem::linkToDashboard('Mon Bilan', 'fa fa-home');

        if ($user instanceof Coach) {
            yield MenuItem::section('Gestion des séances');
            yield MenuItem::linkToCrud('Mes séances', 'fas fa-calendar-alt', Seance::class)
                ->setDefaultSort(['dateHeure' => 'DESC']);
            yield MenuItem::linkToCrud('Exercices', 'fas fa-dumbbell', Exercice::class);
            yield MenuItem::linkToCrud('Mes fiches de paie', 'fas fa-file-invoice-dollar', FicheDePaie::class);
        }

        if ($user instanceof Responsable) {
            yield MenuItem::section('Administration');
            if ($this->isGranted('ROLE_ADMIN')) {
                yield MenuItem::linkToCrud('Responsables', 'fas fa-user-tie', Responsable::class);
            }
            yield MenuItem::linkToCrud('Coachs', 'fas fa-user-friends', Coach::class);
            yield MenuItem::linkToCrud('Sportifs', 'fas fa-running', Sportif::class)
                ->setDefaultSort(['nom' => 'ASC', 'prenom' => 'ASC']);
            yield MenuItem::linkToCrud('Reservations', 'fas fa-calendar-alt', Reservation::class);
            yield MenuItem::section('Gestion');
            yield MenuItem::linkToCrud('Toutes les séances', 'fas fa-calendar-alt', Seance::class)
                ->setDefaultSort(['dateHeure' => 'DESC']);
            yield MenuItem::linkToCrud('Tous les exercices', 'fas fa-dumbbell', Exercice::class);
            yield MenuItem::linkToCrud('Toutes les fiches de paie', 'fas fa-file-invoice-dollar', FicheDePaie::class);
        }
    }

    #[Route('/check-auth', name: 'dashboard_check_auth', methods: ['GET'])]
    public function checkAuth(): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $roles = $user->getRoles();
        if (in_array('ROLE_RESPONSABLE', $roles)) {
            return $this->json(['redirect' => '/admin/responsable']);
        } elseif (in_array('ROLE_COACH', $roles)) {
            return $this->json(['redirect' => '/admin/coach']);
        } elseif (in_array('ROLE_SPORTIF', $roles)) {
            return $this->json(['redirect' => '/admin/sportif']);
        } else {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }
    }
}