<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AngularController extends AbstractController
{
    #[Route('/app', name: 'app_angular')]
    #[Route("/app/{route}", name: "app_angular_route", requirements: ["route" => ".*"])]
    public function index(): Response
    {
        $content = file_get_contents(__DIR__ . '/../../public/app/index.html');
        
        $response = new Response($content);

        return $response;
    }
}
