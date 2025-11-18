<?php

namespace App\Controller;

use App\Repository\HamstersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Hamsters;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

final class HamstersController extends AbstractController
{
    #[Route('/api/hamsters', name: 'hamsters_list', methods: ['GET'])]
    public function getAllHamsterss(HamstersRepository $hamstersRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(
                ['message' => 'Utilisateur non authentifié.'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $listHamsterss = $hamstersRepository->findByOwner($user);
        return $this->json([
            'listHamsterss' => $listHamsterss,
        ], Response::HTTP_OK, [], ['groups' => ['hamster_list']]);
    }

    #[Route('/api/hamsters/{id}', name: 'hamsters_by_id', methods: ['GET'])]
    public function getHamstersById(Hamsters $hamsters): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(
                ['message' => 'Utilisateur non authentifié.'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isOwner = $hamsters->getOwner()->getId() === $user->getId();

        if (!$isAdmin && !$isOwner) {
            return $this->json(
                ['message' => 'Accès refusé.'],
                Response::HTTP_FORBIDDEN
            );
        }

        return $this->json([
            'hamsters' => $hamsters,
        ], Response::HTTP_OK, [], ['groups' => ['hamster_list']]);
    }
}
