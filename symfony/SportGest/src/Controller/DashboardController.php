<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $roles = $user->getRoles();
        
        if (in_array('ROLE_RESPONSABLE', $roles)) {
            return $this->redirectToRoute('responsable_dashboard');
        } elseif (in_array('ROLE_COACH', $roles)) {
            return $this->redirectToRoute('coach_dashboard');
        }

        return $this->redirectToRoute('app_login');
    }
} 