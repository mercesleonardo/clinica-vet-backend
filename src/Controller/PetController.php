<?php

namespace App\Controller;

use App\Entity\Pet;
use App\Entity\Breed;
use App\Entity\User;
use App\Repository\PetRepository;
use App\Repository\BreedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pets', name: 'api_pets_')]
final class PetController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(PetRepository $petRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        /** @var User $user */
        $pets = $petRepository->findBy(['owner' => $user]);

        $data = array_map(function (Pet $pet) {
            return [
                'id' => $pet->getId(),
                'name' => $pet->getName(),
                'gender' => $pet->getGender(),
                'birthDate' => $pet->getBirthDate()?->format('Y-m-d'),
                'breed' => $pet->getBreed()?->getId(),
                'breedName' => $pet->getBreed()?->getName(),
                'owner' => $pet->getOwner()?->getId(),
            ];
        }, $pets);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(PetRepository $petRepository, int $id): JsonResponse
    {
        $pet = $petRepository->find($id);

        if (!$pet) {
            return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $pet->getId(),
            'name' => $pet->getName(),
            'gender' => $pet->getGender(),
            'birthDate' => $pet->getBirthDate()?->format('Y-m-d'),
            'breed' => [ 'id' => $pet->getBreed()?->getId(), 'name' => $pet->getBreed()?->getName() ],
            'owner' => [ 'id' => $pet->getOwner()?->getId(), 'email' => $pet->getOwner()?->getEmail() ],
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, BreedRepository $breedRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        /** @var User $user */

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $name = $data['name'] ?? null;
        $gender = $data['gender'] ?? null;
        $birthDate = $data['birthDate'] ?? null;
        $breedId = $data['breedId'] ?? null;

        if (!$name || !$gender || !$breedId) {
            return $this->json(['error' => 'Missing required fields (name, gender, breedId)'], Response::HTTP_BAD_REQUEST);
        }

        $breed = $breedRepository->find($breedId);
        if (!$breed) {
            return $this->json(['error' => 'Breed not found'], Response::HTTP_BAD_REQUEST);
        }

        $pet = new Pet();
        $pet->setName($name);
        $pet->setGender($gender);
        if ($birthDate) {
            try {
                $pet->setBirthDate(new \DateTime($birthDate));
            } catch (\Exception $e) {
                return $this->json(['error' => 'Invalid birthDate format, expected YYYY-MM-DD'], Response::HTTP_BAD_REQUEST);
            }
        }
        $pet->setBreed($breed);
        $pet->setOwner($user);

        $em->persist($pet);
        $em->flush();

        return $this->json([
            'message' => 'Pet created',
            'id' => $pet->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT','PATCH'])]
    public function update(Request $request, PetRepository $petRepository, EntityManagerInterface $em, BreedRepository $breedRepository, int $id): JsonResponse
    {
        $pet = $petRepository->find($id);
        if (!$pet) {
            return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // only owner or ROLE_ADMIN (if present) can update
        if (($pet->getOwner()?->getEmail() !== $user->getUserIdentifier()) && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $pet->setName($data['name']);
        }
        if (isset($data['gender'])) {
            $pet->setGender($data['gender']);
        }
        if (isset($data['birthDate'])) {
            try {
                $pet->setBirthDate(new \DateTime($data['birthDate']));
            } catch (\Exception $e) {
                return $this->json(['error' => 'Invalid birthDate format, expected YYYY-MM-DD'], Response::HTTP_BAD_REQUEST);
            }
        }
        if (isset($data['breedId'])) {
            $breed = $breedRepository->find($data['breedId']);
            if (!$breed) {
                return $this->json(['error' => 'Breed not found'], Response::HTTP_BAD_REQUEST);
            }
            $pet->setBreed($breed);
        }

        $em->flush();

        return $this->json(['message' => 'Pet updated']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(PetRepository $petRepository, EntityManagerInterface $em, int $id): JsonResponse
    {
        $pet = $petRepository->find($id);
        if (!$pet) {
            return $this->json(['error' => 'Pet not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        /** @var User $user */

        if (($pet->getOwner()?->getEmail() !== $user->getUserIdentifier()) && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($pet);
        $em->flush();

        return $this->json([
            'message' => 'Pet deleted',
            'pet' => [
                'name' => $pet->getName(),
            ],
        ]);
    }
}
