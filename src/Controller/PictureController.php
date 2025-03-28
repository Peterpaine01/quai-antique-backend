<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Repository\PictureRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Cloudinary\Cloudinary;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('api/picture', name: 'app_api_picture_')]
class PictureController extends AbstractController
{
    private Cloudinary $cloudinary;
    private $serializer;

    public function __construct(private EntityManagerInterface $manager, private RestaurantRepository $restaurantRepository, private PictureRepository $pictureRepository, SerializerInterface $serializer)
    {
        $this->cloudinary = new Cloudinary($_ENV['CLOUDINARY_URL']);
        $this->serializer = $serializer;
        $this->manager = $manager;
        $this->restaurantRepository = $restaurantRepository;
        $this->pictureRepository = $pictureRepository;
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(
        Request $request, ValidatorInterface $validator
    ): JsonResponse {
        $file = $request->files->get('image');
        $title = $request->request->get('title');
        $restaurantId = $request->request->get('restaurant');

        if (!$file || !$title || !$restaurantId) {
            return $this->json(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifier si le restaurant existe
        $restaurant = $this->restaurantRepository->find($restaurantId);
        if (!$restaurant) {
            return $this->json(['error' => 'Restaurant not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json(['error' => 'Unauthorized format'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() > 5 * 1024 * 1024) { 
            return $this->json(['error' => 'Picture too large'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            // Dossier de stockage sur Cloudinary
            $folderName = 'restaurants/' . preg_replace('/\s+/', '_', $restaurant->getName());

            // Upload de l’image
            $upload = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                ['folder' => $folderName]
            );

            // Création de l'image
            $picture = new Picture();
            $picture->setTitle($title);
            $picture->setSlug($upload['secure_url']);
            $picture->setCreatedAt(new \DateTimeImmutable());
            $picture->setRestaurant($restaurant);

             // Validation des données
             $errors = $validator->validate($picture);
             if (count($errors) > 0) {
                 return new JsonResponse($this->serializer->serialize($errors, 'json'), 400, [], true);
             }

            $this->manager->persist($picture);
            $this->manager->flush();

            $pictureData = $this->serializer->serialize($picture, 'json', ['groups' => 'picture:read']);
            $location = $this->generateUrl(
                'app_api_restaurant_show',
                ['id' => $picture->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );

            return new JsonResponse([
                'message' => "Picture created with ID {$picture->getId()}",
                'data' => json_decode($pictureData, true) 
            ], JsonResponse::HTTP_CREATED, ["Location" => $location]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Cloudinary upload failed: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $pictures = $this->pictureRepository->findAll();
    
        $data = $this->serializer->serialize($pictures, 'json', ['groups' => 'picture:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $picture = $this->pictureRepository->find($id);

        if (!$picture) {
            return $this->json(['error' => 'Picture not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($picture, 'json', ['groups' => 'picture:read']);
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(
        Request $request,
        int $id
    ): JsonResponse {
        $picture = $this->pictureRepository->find($id);
        if (!$picture) {
            return $this->json(['error' => 'Picture not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $title = $data['title'];

    if ($title) {
        $picture->setTitle($title);
        $picture->setUpdatedAt(new \DateTimeImmutable());
        $this->manager->flush();
    } else {
        return $this->json(['error' => 'No title provided'], JsonResponse::HTTP_BAD_REQUEST);
    }

        $picture->setUpdatedAt(new \DateTimeImmutable());

        $this->manager->flush();

        $pictureData = $this->serializer->serialize($picture, 'json', ['groups' => 'picture:read']);
        

        return new JsonResponse([
            'message' => "Picture updated successuly}",
            'data' => json_decode($pictureData, true) 
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse 
    {
        $picture = $this->pictureRepository->find($id);

        if (!$picture) {
            return $this->json(['error' => 'Image non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            // Supprimer l'image sur Cloudinary avec invalidation du cache
            $this->cloudinary->uploadApi()->destroy($picture->getSlug(), ['invalidate' => true]);

            // Supprimer de la BDD
            $this->manager->remove($picture);
            $this->manager->flush();

            return $this->json(['message' => 'Image supprimée avec succès']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
