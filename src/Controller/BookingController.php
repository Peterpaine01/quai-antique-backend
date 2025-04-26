<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Restaurant; 
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/api/booking', name: 'api_booking_')]
class BookingController extends AbstractController
{
    private EntityManagerInterface $manager;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $manager, SerializerInterface $serializer)
    {
        $this->manager = $manager;
        $this->serializer = $serializer;
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(BookingRepository $bookingRepository): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
        }
        
        $bookings = $bookingRepository->findAll();
        $data = $this->serializer->serialize($bookings, 'json', ['groups' => 'booking:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/show/{uuid}', name: 'show', methods: ['GET'])]
    public function show(string $uuid, BookingRepository $bookingRepository): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $booking = $bookingRepository->findOneBy(['uuid' => $uuid]);

        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($booking->getClient()->getId() !== $currentUser->getId() && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
        }

        $bookingData = $this->serializer->serialize($booking, 'json', ['groups' => 'booking:read']);

        return new JsonResponse($bookingData, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (empty($data['guestNumber']) || empty($data['orderDate']) || empty($data['orderHour']) || empty($data['restaurantId'])) {
            return $this->json(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $restaurant = $this->manager->getRepository(Restaurant::class)->find($data['restaurantId']);
        if (!$restaurant) {
            return $this->json(['error' => 'Restaurant not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        
        $booking = new Booking();
        $booking->setUuid(Uuid::v4()->toRfc4122());
        $booking->setGuestNumber($data['guestNumber']);
        $booking->setOrderDate(new \DateTime($data['orderDate']));
        $booking->setOrderHour(new \DateTime($data['orderHour']));
        $booking->setAllergy($data['allergy'] ?? null);
        $booking->setCreatedAt(new \DateTimeImmutable());
        $booking->setRestaurant($restaurant);
        $booking->setClient($currentUser); 

        $this->manager->persist($booking);
        $this->manager->flush();

        return $this->json($booking, JsonResponse::HTTP_CREATED, [], ['groups' => 'booking:read']);
    }


    #[Route('/edit/{uuid}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, string $uuid, BookingRepository $bookingRepository): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $booking = $bookingRepository->findOneBy(['uuid' => $uuid]);

        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($booking->getClient()->getId() !== $currentUser->getId() && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['guestNumber'])) {
            $booking->setGuestNumber($data['guestNumber']);
        }
        if (isset($data['orderDate'])) {
            $booking->setOrderDate(new \DateTime($data['orderDate']));
        }
        if (isset($data['orderHour'])) {
            $booking->setOrderHour(new \DateTime($data['orderHour']));
        }
        if (array_key_exists('allergy', $data)) {
            $booking->setAllergy($data['allergy']);
        }

        $booking->setUpdatedAt(new \DateTime());

        $this->manager->flush();

        return $this->json($booking, 200, [], ['groups' => 'booking:read']);
    }

    #[Route('/delete/{uuid}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $uuid, BookingRepository $bookingRepository): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $booking = $bookingRepository->findOneBy(['uuid' => $uuid]);

        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($booking->getClient()->getId() !== $currentUser->getId() && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
        }

        $this->manager->remove($booking);
        $this->manager->flush();

        return $this->json(['message' => 'Booking deleted'], 200);
    }
}
