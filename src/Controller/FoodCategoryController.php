<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Food;
use App\Entity\FoodCategory;
use App\Repository\FoodCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/food-category', name: 'api_food_category_')]
class FoodCategoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager
    ) {}

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(FoodCategoryRepository $repository): JsonResponse
    {
        $associations = $repository->findAll();

        $data = array_map(function (FoodCategory $fc) {
            return [
                'id' => $fc->getId(),
                'food' => $fc->getFood()?->getUuid(),
                'category' => $fc->getCategory()?->getUuid(),
            ];
        }, $associations);

        return $this->json($data);
    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(FoodCategory $foodCategory): JsonResponse
    {
        return $this->json([
            'id' => $foodCategory->getId(),
            'food' => $foodCategory->getFood()?->getUuid(),
            'category' => $foodCategory->getCategory()?->getUuid(),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['foodId']) || !isset($data['categoryId'])) {
            return $this->json(['error' => 'Missing parameters'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $food = $this->manager->getRepository(Food::class)->find($data['foodId']);
        $category = $this->manager->getRepository(Category::class)->find($data['categoryId']);

        if (!$food || !$category) {
            return $this->json(['error' => 'Food or Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $association = new FoodCategory();
        $association->setFood($food);
        $association->setCategory($category);

        $this->manager->persist($association);
        $this->manager->flush();

        return $this->json(['message' => 'FoodCategory created', 'id' => $association->getId()], JsonResponse::HTTP_CREATED);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(FoodCategory $foodCategory): JsonResponse
    {
        $this->manager->remove($foodCategory);
        $this->manager->flush();

        return $this->json(['message' => 'FoodCategory deleted'], JsonResponse::HTTP_NO_CONTENT);
    }
}
