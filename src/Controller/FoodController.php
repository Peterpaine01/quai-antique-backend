<?php

namespace App\Controller;

use App\Entity\Food;
use App\Repository\FoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/api/food', name: 'api_food_')]
class FoodController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SerializerInterface $serializer
    ) {}

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(FoodRepository $foodRepository): JsonResponse
    {
        $foods = $foodRepository->findAll();
        $data = $this->serializer->serialize($foods, 'json', ['groups' => 'food:read']);
        return new JsonResponse(json_decode($data), JsonResponse::HTTP_OK);
    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(Food $food): JsonResponse
    {
        $data = $this->serializer->serialize($food, 'json', ['groups' => 'food:read']);
        return new JsonResponse(json_decode($data), JsonResponse::HTTP_OK);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $food = $this->serializer->deserialize($request->getContent(), Food::class, 'json');
        $food->setUuid(Uuid::v4());
        $food->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($food);
        $this->manager->flush();

        return new JsonResponse(['message' => 'Food created', 'uuid' => $food->getUuid()], JsonResponse::HTTP_CREATED);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['PUT', 'PATCH'])]
    public function edit(Request $request, Food $food): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $food->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $food->setDescription($data['description']);
        }

        if (isset($data['price'])) {
            $food->setPrice((float)$data['price']);
        }

        $food->setUpdatedAt(new \DateTime());

        $this->manager->flush();

        return new JsonResponse(['message' => 'Food updated'], JsonResponse::HTTP_OK);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Food $food): JsonResponse
    {
        $this->manager->remove($food);
        $this->manager->flush();

        return new JsonResponse(['message' => 'Food deleted'], JsonResponse::HTTP_NO_CONTENT);
    }
}
