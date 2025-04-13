<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface; 
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;


#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, private SerializerInterface $serializer)
    {
  
    }

    #[Route('/registration', name: 'registration', methods: ['POST'])]
    public function registrer(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($user); 
        $this->manager->flush();
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
        );;
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $JWTManager,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['message' => 'Missing credentials'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $JWTManager->create($user);

        return new JsonResponse([
            'message' => 'Login successful',
            'token' => $token,
            'roles' => $user->getRoles()
        ]);
    }


    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {

        return new JsonResponse(['message' => 'Déconnexion réussie']);
    }
}
