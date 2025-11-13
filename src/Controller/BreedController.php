<?php

namespace App\Controller;

use App\Entity\Breed;
use App\Repository\BreedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/breeds', name: 'api_breeds_')]
final class BreedController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(BreedRepository $breedRepository): JsonResponse
    {
        $breeds = $breedRepository->findAll();

        $data = array_map(function (Breed $breed) {
            return [
                'id' => $breed->getId(),
                'name' => $breed->getName(),
                'species' => $breed->getSpecies(),
            ];
        }, $breeds);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(BreedRepository $breedRepository, int $id): JsonResponse
    {
        $breed = $breedRepository->find($id);
        if (!$breed) {
            return $this->json(['error' => 'Breed not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $breed->getId(),
            'name' => $breed->getName(),
            'species' => $breed->getSpecies(),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $name = $data['name'] ?? null;
        $species = $data['species'] ?? null;

        if (!$name || !$species) {
            return $this->json(['error' => 'Missing required fields (name, species)'], Response::HTTP_BAD_REQUEST);
        }

        $breed = new Breed();
        $breed->setName($name);
        $breed->setSpecies($species);

        $em->persist($breed);
        $em->flush();

        return $this->json(['message' => 'Breed created', 'id' => $breed->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT','PATCH'])]
    public function update(Request $request, BreedRepository $breedRepository, EntityManagerInterface $em, int $id): JsonResponse
    {
        $breed = $breedRepository->find($id);
        if (!$breed) {
            return $this->json(['error' => 'Breed not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $breed->setName($data['name']);
        }
        if (isset($data['species'])) {
            $breed->setSpecies($data['species']);
        }

        $em->flush();

        return $this->json(['message' => 'Breed updated']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(BreedRepository $breedRepository, EntityManagerInterface $em, int $id): JsonResponse
    {
        $breed = $breedRepository->find($id);
        if (!$breed) {
            return $this->json(['error' => 'Breed not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($breed);
        $em->flush();

        return $this->json(['message' => 'Breed deleted']);
    }
}
