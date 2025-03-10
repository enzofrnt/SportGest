<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CategorieRepository;
final class CategoryController extends AbstractController
{
    #[Route('/api/category', name: 'app_api_category')]
    public function index(CategorieRepository $repo): JsonResponse
    {
        $categories = $repo->findAll();
        return $this->json($categories, JsonResponse::HTTP_OK, [], ['groups' => ['monGroupe']]);
    }
}
