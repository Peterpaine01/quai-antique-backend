<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/account', name: 'app_api_account_')]
class AccountController extends AbstractController
{
    private $serializer;
    private $repository;

    public function __construct(
        private EntityManagerInterface $manager,
        UserRepository $repository,
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
        $this->repository = $repository;
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $apiToken = $request->headers->get('X-AUTH-TOKEN');

        if (!$apiToken) {
            return $this->json(['error' => 'Missing X-AUTH-TOKEN header'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $this->repository->findOneBy(['apiToken' => $apiToken]);

        if (!$user) {
            return $this->json(['error' => 'Invalid API token'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/edit', name: 'edit', methods: ['PUT', 'PATCH'])]
    public function edit(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $apiToken = $request->headers->get('X-AUTH-TOKEN');

        if (!$apiToken) {
            return $this->json(['error' => 'Missing X-AUTH-TOKEN header'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $this->repository->findOneBy(['apiToken' => $apiToken]);

        if (!$user) {
            return $this->json(['error' => 'Invalid API token'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (isset($data['firstname'])) {
            $user->setFirstname($data['firstname']);
        }

        if (isset($data['lastname'])) {
            $user->setLastname($data['lastname']);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['guestNumber'])) {
            $user->setGuestNumber((int) $data['guestNumber']);
        }

        if (isset($data['allergy'])) {
            $user->setAllergy($data['allergy']);
        }

        if (isset($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }
        
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        $responseData = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);

        return new JsonResponse($responseData, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $apiToken = $request->headers->get('X-AUTH-TOKEN');

        if (!$apiToken) {
            return $this->json(['error' => 'Missing X-AUTH-TOKEN header'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $this->repository->findOneBy(['apiToken' => $apiToken]);

        if (!$user) {
            return $this->json(['error' => 'Invalid API token'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $this->manager->remove($user);
        $this->manager->flush();

        return $this->json(['message' => 'Account deleted successfully.'], JsonResponse::HTTP_OK);
    }
}
