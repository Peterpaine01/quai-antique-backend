<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\Serializer\SerializerInterface; 

#[Route('api/restaurant', name: 'app_api_restaurant_')]
class RestaurantController extends AbstractController
{
    private $serializer;

    public function __construct(private EntityManagerInterface $manager, private RestaurantRepository $repository, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/create', name: 'create', methods: 'POST')]
    public function new(Request $request, EntityManagerInterface $manager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['name'], $data['owner_id'], $data['max_guest'])) {
            return new JsonResponse(
                ['error' => 'Invalid data. Required: name, owner_id, max_guest'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $restaurant = new Restaurant();
        $restaurant->setName($data['name']);
        $restaurant->setDescription($data['description'] ?? null);
        $restaurant->setMaxGuest($data['max_guest']);
        $restaurant->setCreatedAt(new \DateTimeImmutable());
        $restaurant->setUpdatedAt(null);

        $owner = $manager->getRepository(User::class)->find($data['owner_id']);
        if (!$owner) {
            return new JsonResponse(
                ['error' => 'Invalid owner_id'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        $restaurant->setOwner($owner);

        $restaurant->setAmOpeningTime(isset($data['am_opening_time']) ? $data['am_opening_time'] : null);
        $restaurant->setPmOpeningTime(isset($data['pm_opening_time']) ? $data['pm_opening_time'] : null);

        $manager->persist($restaurant);
        $manager->flush();

        $restaurantData = $this->serializer->serialize($restaurant, 'json', ['groups' => 'restaurant:read']);
        $location = $this->generateUrl(
            'app_api_restaurant_show',
            ['id' => $restaurant->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse(
            [
                'message' => "Restaurant created with ID {$restaurant->getId()}",
                'data' => json_decode($restaurantData, true) 
            ],
            JsonResponse::HTTP_CREATED,
            ["Location" => $location]
        );
    }

    #[Route('/show/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if (!$restaurant) {
            return new JsonResponse(
                ['error' => "No Restaurant found for {$id}"],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $context = [
            'groups' => ['restaurant:read', 'booking:read'],
            'enable_max_depth' => true,
            'circular_reference_handler' => function ($object) {
                return $object->getId(); 
            }, 
        ];
        
        $restaurantData = $this->serializer->serialize($restaurant, 'json', $context);

        return new JsonResponse($restaurantData, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/edit/{id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id, Request $request): RedirectResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if (!$restaurant) {
            throw $this->createNotFoundException("No Restaurant found for {$id} id");
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $restaurant->setName($data['name']);
        }
        if (isset($data['description'])) {
            $restaurant->setDescription($data['description']);
        }
        if (isset($data['max_guest'])) {
            $restaurant->setMaxGuest($data['max_guest']);
        }
        if (isset($data['am_opening_time'])) {
            $restaurant->setAmOpeningTime($data['am_opening_time']);
        }
        if (isset($data['pm_opening_time'])) {
            $restaurant->setPmOpeningTime($data['pm_opening_time']);
        }

        $restaurant->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        return $this->redirectToRoute('app_api_restaurant_show', ['id' => $restaurant->getId()]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if (!$restaurant) {
            return new JsonResponse(
                ['error' => "No Restaurant found for {$id}"],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $this->manager->remove($restaurant);
        $this->manager->flush();

        return new JsonResponse(
            ['message' => "Restaurant deleted successfully"],
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
