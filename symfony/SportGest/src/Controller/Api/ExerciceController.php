<?php

namespace App\Controller\Api;

use App\Entity\Exercice;
use App\Repository\ExerciceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/exercices')]
class ExerciceController extends AbstractController
{
    #[Route('', name: 'api_exercices_list', methods: ['GET'])]
    public function listExercices(ExerciceRepository $exerciceRepository, Request $request): JsonResponse
    {
        // Filtres optionnels
        $difficulte = $request->query->get('difficulte');
        $dureeMax = $request->query->get('duree_max');

        // Construction de la requête
        $queryBuilder = $exerciceRepository->createQueryBuilder('e');

        // Appliquer le filtre de difficulté
        if ($difficulte) {
            $queryBuilder
                ->andWhere('e.difficulte = :difficulte')
                ->setParameter('difficulte', strtolower($difficulte));
        }

        // Appliquer le filtre de durée maximale
        if ($dureeMax) {
            $queryBuilder
                ->andWhere('e.dureeEstimee <= :dureeMax')
                ->setParameter('dureeMax', (int)$dureeMax);
        }

        // Exécuter la requête
        $exercices = $queryBuilder->getQuery()->getResult();

        $data = [];
        foreach ($exercices as $exercice) {
            $data[] = [
                'id' => $exercice->getId(),
                'nom' => $exercice->getNom(),
                'description' => $exercice->getDescription(),
                'difficulte' => $exercice->getDifficulte()->value,
                'dureeMinutes' => $exercice->getDureeEstimee(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_exercice_show', methods: ['GET'])]
    public function showExercice(Exercice $exercice): JsonResponse
    {
        return $this->json([
            'id' => $exercice->getId(),
            'nom' => $exercice->getNom(),
            'description' => $exercice->getDescription(),
            'dureeEstimee' => $exercice->getDureeEstimee(),
            'difficulte' => $exercice->getDifficulte()->name,
        ]);
    }

    #[Route('', name: 'api_exercice_create', methods: ['POST'])]
    #[IsGranted('ROLE_COACH')]
    public function createExercice(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (
            !isset($data['nom']) || !isset($data['description']) ||
            !isset($data['dureeEstimee']) || !isset($data['difficulte'])
        ) {
            return $this->json(['error' => 'Données incomplètes'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $exercice = new Exercice();
            $exercice->setNom($data['nom']);
            $exercice->setDescription($data['description']);
            $exercice->setDureeEstimee($data['dureeEstimee']);
            $exercice->setDifficulte($data['difficulte']);

            $entityManager->persist($exercice);
            $entityManager->flush();

            return $this->json([
                'message' => 'Exercice créé avec succès',
                'id' => $exercice->getId()
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la création de l\'exercice: ' . $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'api_exercice_update', methods: ['PUT'])]
    #[IsGranted('ROLE_COACH')]
    public function updateExercice(Exercice $exercice, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            if (isset($data['nom'])) {
                $exercice->setNom($data['nom']);
            }

            if (isset($data['description'])) {
                $exercice->setDescription($data['description']);
            }

            if (isset($data['dureeEstimee'])) {
                $exercice->setDureeEstimee($data['dureeEstimee']);
            }

            if (isset($data['difficulte'])) {
                $exercice->setDifficulte($data['difficulte']);
            }

            $entityManager->flush();

            return $this->json([
                'message' => 'Exercice mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour de l\'exercice: ' . $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'api_exercice_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_COACH')]
    public function deleteExercice(Exercice $exercice, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityManager->remove($exercice);
            $entityManager->flush();

            return $this->json([
                'message' => 'Exercice supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression de l\'exercice: ' . $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}