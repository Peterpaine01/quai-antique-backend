<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


#[Route('/api/category', name: 'api_category_')]
class CategoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SerializerInterface $serializer
    ) {}

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();
        $data = $this->serializer->serialize($categories, 'json', ['groups' => 'category:read']);
        return new JsonResponse(json_decode($data), JsonResponse::HTTP_OK);
    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        $data = $this->serializer->serialize($category, 'json', ['groups' => 'category:read']);
        return new JsonResponse(json_decode($data), JsonResponse::HTTP_OK);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $category = $this->serializer->deserialize($request->getContent(), Category::class, 'json');
        $category->setUuid(Uuid::v4());
        $category->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($category);
        $this->manager->flush();
        $categoryData = $this->serializer->serialize($category, 'json', ['groups' => 'category:read']);
        $location = $this->generateUrl('api_category_show', ['id' => $category->getId()], UrlGeneratorInterface::ABSOLUTE_URL);


        return new JsonResponse([
            'message' => "Category created with ID {$category->getId()}",
            'data' => json_decode($categoryData, true) 
        ], JsonResponse::HTTP_CREATED, ["Location" => $location]);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['PUT', 'PATCH'])]
    public function edit(Request $request, Category $category): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $category->setTitle($data['title']);
        }

        if (isset($data['icon'])) {
            $category->setIcon($data['icon']);
        }

        $category->setUpdatedAt(new \DateTime());

        $this->manager->flush();

        return new JsonResponse(['message' => 'Category updated'], JsonResponse::HTTP_OK);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Category $category): JsonResponse
    {
        $this->manager->remove($category);
        $this->manager->flush();

        return new JsonResponse(['message' => 'Category deleted'], JsonResponse::HTTP_NO_CONTENT);
    }
}
