<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;

#[Route('api/restaurant', name: 'app_api_restaurant_')]
class RestaurantController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, private RestaurantRepository $repository)
    {
    }

    #[Route('/create', name: 'create', methods: 'POST')]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
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

        // Gestion de l'owner_id (relation avec User)
        $owner = $entityManager->getRepository(User::class)->find($data['owner_id']);
        if (!$owner) {
            return new JsonResponse(
                ['error' => 'Invalid owner_id'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        $restaurant->setOwner($owner);

        // Tableau horaires d'ouverture
        $restaurant->setAmOpeningTime(isset($data['am_opening_time']) ? $data['am_opening_time'] : null);
        $restaurant->setPmOpeningTime(isset($data['pm_opening_time']) ? $data['pm_opening_time'] : null);

        // Sauvegarde en base
        $entityManager->persist($restaurant);
        $entityManager->flush();

        return new JsonResponse(
            ['message' => "Restaurant created with ID {$restaurant->getId()}"],
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route('/show/{id}', name: 'show', methods: 'GET' )]
    public function show(int $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if (!$restaurant) {
            throw $this->createNotFoundException("No Restaurant found for {$id} id");
        }

        return new JsonResponse(
            ['message' => "A Restaurant was found: {$restaurant->getName()} for {$restaurant->getId()} id"]
        );
    }

    #[Route('/edit/{id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id): RedirectResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);

        if (!$restaurant) {
            throw $this->createNotFoundException("No Restaurant found for {$id} id");
        }

        $restaurant->setName('Restaurant name updated');
        $this->manager->flush();

        return $this->redirectToRoute('app_api_restaurant_show', ['id' => $restaurant->getId()]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);
        if (!$restaurant) {
            throw $this->createNotFoundException("No Restaurant found for {$id} id");
        }

        $this->manager->remove($restaurant);
        $this->manager->flush();

        return new JsonResponse(
            ['message' => "Restaurant resource deleted"],
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
