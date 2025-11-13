<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/addresses', name: 'api_addresses_')]
final class AddressController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(AddressRepository $addressRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $addresses = $addressRepository->findBy(['user' => $user]);

        $data = array_map(function (Address $address) {
            return [
                'id' => $address->getId(),
                'street' => $address->getStreet(),
                'number' => $address->getNumber(),
                'district' => $address->getDistrict(),
                'city' => $address->getCity(),
                'state' => $address->getState(),
                'zipCode' => $address->getZipCode(),
            ];
        }, $addresses);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(AddressRepository $addressRepository, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $address = $addressRepository->find($id);
        if (!$address || $address->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Address not found or forbidden'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $address->getId(),
            'street' => $address->getStreet(),
            'number' => $address->getNumber(),
            'district' => $address->getDistrict(),
            'city' => $address->getCity(),
            'state' => $address->getState(),
            'zipCode' => $address->getZipCode(),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $street = $data['street'] ?? null;
        $city = $data['city'] ?? null;
        $state = $data['state'] ?? null;
        $zipCode = $data['zipCode'] ?? null;

        if (!$street || !$city || !$state || !$zipCode) {
            return $this->json(['error' => 'Missing required fields (street, city, state, zipCode)'], Response::HTTP_BAD_REQUEST);
        }

        $address = new Address();
        $address->setStreet($street);
        $address->setNumber($data['number'] ?? null);
        $address->setDistrict($data['district'] ?? null);
        $address->setCity($city);
        $address->setState($state);
        $address->setZipCode($zipCode);
        $address->setUser($user);

        $em->persist($address);
        $em->flush();

        return $this->json(
            [
                'message' => 'Address created',
                'address' => [
                    'id' => $address->getId(),
                    'street' => $address->getStreet(),
                    'number' => $address->getNumber(),
                    'district' => $address->getDistrict(),
                    'city' => $address->getCity(),
                    'state' => $address->getState(),
                    'zipCode' => $address->getZipCode(),
                ]
            ],Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, AddressRepository $addressRepository, EntityManagerInterface $em, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $address = $addressRepository->find($id);
        if (!$address || $address->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Address not found or forbidden'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['street'])) {
            $address->setStreet($data['street']);
        }
        if (array_key_exists('number', $data)) {
            $address->setNumber($data['number']);
        }
        if (array_key_exists('district', $data)) {
            $address->setDistrict($data['district']);
        }
        if (isset($data['city'])) {
            $address->setCity($data['city']);
        }
        if (isset($data['state'])) {
            $address->setState($data['state']);
        }
        if (isset($data['zipCode'])) {
            $address->setZipCode($data['zipCode']);
        }

        $em->flush();

        return $this->json(['message' => 'Address updated']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(AddressRepository $addressRepository, EntityManagerInterface $em, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $address = $addressRepository->find($id);
        if (!$address || $address->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Address not found or forbidden'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($address);
        $em->flush();

        return $this->json(['message' => 'Address deleted']);
    }
}
