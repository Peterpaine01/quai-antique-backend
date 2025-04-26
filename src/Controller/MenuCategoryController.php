<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Menu;
use App\Entity\MenuCategory;
use App\Repository\MenuCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/menu-categories', name: 'menu_category_')]
class MenuCategoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager
    ) {}

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(MenuCategoryRepository $repository): JsonResponse
    {
        $associations = $repository->findAll();

        $data = array_map(function (MenuCategory $mc) {
            return [
                'id' => $mc->getId(),
                'menu' => $mc->getMenu()?->getUuid(),
                'category' => $mc->getCategory()?->getUuid(),
            ];
        }, $associations);

        return $this->json($data);
    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(MenuCategory $menuCategory): JsonResponse
    {
        return $this->json([
            'id' => $menuCategory->getId(),
            'menu' => $menuCategory->getMenu()?->getUuid(),
            'category' => $menuCategory->getCategory()?->getUuid(),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['menuId']) || !isset($data['categoryId'])) {
            return $this->json(['error' => 'Missing parameters'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $menu = $this->manager->getRepository(Menu::class)->find($data['menuId']);
        $category = $this->manager->getRepository(Category::class)->find($data['categoryId']);

        if (!$menu || !$category) {
            return $this->json(['error' => 'Menu or Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $association = new MenuCategory();
        $association->setMenu($menu);
        $association->setCategory($category);

        $this->manager->persist($association);
        $this->manager->flush();

        return $this->json(['message' => 'MenuCategory created', 'id' => $association->getId()], JsonResponse::HTTP_CREATED);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(MenuCategory $menuCategory): JsonResponse
    {
        $this->manager->remove($menuCategory);
        $this->manager->flush();

        return $this->json(['message' => 'MenuCategory deleted'], JsonResponse::HTTP_NO_CONTENT);
    }
}
