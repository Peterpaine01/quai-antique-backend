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
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
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

        $this->manager->persist($user);
        $this->manager->flush();
        $userData = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
        $location = $this->generateUrl(
            'app_api_user_show',
            ['uuid' => $user->getUuid()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse(
            [
                'message' => "User created with UUID {$user->getUuid()}",
                'data' => json_decode($userData, true) 
            ],
            JsonResponse::HTTP_CREATED,
            ["Location" => $location]
        );

    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(UserRepository $userRepository): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
        }

        $users = $userRepository->findAll();
        $data = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/show/{uuid}', name: 'show', methods: ['GET'])]
    public function show(UserRepository $userRepository, string $uuid): JsonResponse
    {
        $user = $userRepository->findOneBy(['uuid' => $uuid]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $userData = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);

        return new JsonResponse($userData, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/edit/{uuid}', name: 'edit', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, UserRepository $userRepository, string $uuid): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if ($currentUser->getUuid() !== $uuid) {
            return $this->json(['error' => 'Access denied: you can only edit your own account'], JsonResponse::HTTP_FORBIDDEN);
        }

        $user = $userRepository->findOneBy(['uuid' => $uuid]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['guestNumber'])) {
            $user->setGuestNumber($data['guestNumber']);
        }
        if (isset($data['allergy'])) {
            $user->setAllergy($data['allergy']);
        }
        if (isset($data['roles'])) {
            if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
                // Autorisé pour ADMIN uniquement
                $user->setRoles($data['roles']);
            } else {
                return $this->json(['error' => 'You are not allowed to change your roles'], JsonResponse::HTTP_FORBIDDEN);
            }
        }

        $user->setUpdatedAt(new \DateTime());

        $this->manager->flush();

        return $this->json(['message' => 'User updated successfully']);
    }


    #[Route('/delete/{uuid}', name: 'delete', methods: ['DELETE'])]
    public function delete(UserRepository $userRepository, string $uuid): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied: You do not have permission to delete users'], JsonResponse::HTTP_FORBIDDEN);
        }

        $user = $userRepository->findOneBy(['uuid' => $uuid]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->manager->remove($user);
        $this->manager->flush();

        return $this->json(['message' => 'User deleted successfully']);
    }

}
