<?php

namespace App\Controller;

use App\Repository\HamstersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Hamsters;
use Symfony\Component\HttpFoundation\Response;

final class HamstersController extends AbstractController
{
    #[Route('/api/hamsters', name: 'hamsters_list', methods: ['GET'])]
    public function getAllHamsterss(HamstersRepository $hamstersRepository): JsonResponse
    {
        $listHamsterss = $hamstersRepository->findAll();
        return $this->json([
            'listHamsterss' => $listHamsterss,
        ], Response::HTTP_OK);
    }

    #[Route('/api/hamsters/{id}', name: 'hamsters_by_id', methods: ['GET'])]
    public function getHamstersById(Hamsters $hamsters): JsonResponse
    {
        return $this->json([
            'hamsters' => $hamsters,
        ], Response::HTTP_OK);
    }
}
