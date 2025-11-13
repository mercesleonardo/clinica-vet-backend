<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
       $user = $this->getUser();

       if (!$user instanceof User) {
           return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
       }

       return $this->json([
           'id' => $user->getId(),
           'firstName' => $user->getFirstName(),
           'lastName' => $user->getLastName(),
           'email' => $user->getEmail(),
           'phone' => $user->getPhone(),
           'roles' => $user->getRoles(),
       ]);
    }
}
