<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface; 
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('api/user', name: 'app_api_user_')]
class UserController extends AbstractController
{
    private $serializer;

    public function __construct(private EntityManagerInterface $manager, private UserRepository $repository, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Invalid data. Required: email, password'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);
        $user->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();
        $userData = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
        $location = $this->generateUrl(
            'app_api_user_show',
            ['id' => $user->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse(
            [
                'message' => "User created with ID {$user->getId()}",
                'data' => json_decode($userData, true) 
            ],
            JsonResponse::HTTP_CREATED,
            ["Location" => $location]
        );

    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(UserRepository $userRepository, int $id): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $userData = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);

        return new JsonResponse($userData, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();
        $data = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['PUT'])]
    public function update(Request $request, EntityManagerInterface $manager, UserRepository $userRepository, int $id): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        $user->setUpdatedAt(new \DateTime());

        $manager->flush();

        return $this->json(['message' => 'User updated successfully']);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $manager, UserRepository $userRepository, int $id): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $manager->remove($user);
        $manager->flush();

        return $this->json(['message' => 'User deleted successfully']);
    }
}
