<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Article;
use App\Entity\Categorie;
use App\Entity\Utilisateur;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            // ->renderSidebarMinimized()
            ->setTitle('Poc Articles');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToCrud('Categories', 'fa fa-folder', Categorie::class);
        yield MenuItem::linkToCrud('Articles', 'fa fa-file-text', Article::class);
        yield MenuItem::section('Configuration');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user', Utilisateur::class);
    }
}
