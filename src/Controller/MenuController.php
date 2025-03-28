<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\Restaurant;
use App\Repository\MenuRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('api/menu', name: 'app_api_menu_')]
class MenuController extends AbstractController
{
    private $serializer;

    public function __construct(private EntityManagerInterface $manager, private MenuRepository $menuRepository, private RestaurantRepository $restaurantRepository, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->manager = $manager;
        $this->restaurantRepository = $restaurantRepository;
        $this->menuRepository = $menuRepository;
    }


    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        dump('price', $data['price']);

        if (!isset($data['title'], $data['description'], $data['price'], $data['restaurant'])) {
            return $this->json(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $restaurant = $this->restaurantRepository->find($data['restaurant']);
        if (!$restaurant) {
            return $this->json(['error' => 'Restaurant not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $menu = new Menu();
        $menu->setTitle($data['title']);
        $menu->setDescription($data['description']);
        $menu->setPrice($data['price']);
        $menu->setRestaurant($restaurant);
        $menu->setCreatedAt(new \DateTimeImmutable());


        $errors = $validator->validate($menu);
        if (count($errors) > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), 400, [], true);
        }

        $this->manager->persist($menu);
        $this->manager->flush();

        $menuData = $this->serializer->serialize($menu, 'json', ['groups' => 'menu:read']);
        $location = $this->generateUrl(
            'app_api_menu_show',
            ['id' => $menu->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse([
            'message' => "Menu created with ID {$menu->getId()}",
            'data' => json_decode($menuData, true) 
        ], JsonResponse::HTTP_CREATED, ["Location" => $location]);
    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $menu = $this->menuRepository->find($id);

        if (!$menu) {
            return $this->json(['error' => 'Menu not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($menu, 'json', ['groups' => 'menu:read']);
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }
}
 