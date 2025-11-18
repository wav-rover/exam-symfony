<?php

namespace App\Controller;

use App\Repository\HamstersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    private function checkHamsterAccess(Hamsters $hamsters): ?JsonResponse
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

        return null;
    }

    #[Route('/api/hamsters/{id}', name: 'hamsters_by_id', methods: ['GET'])]
    public function getHamstersById(Hamsters $hamsters): JsonResponse
    {
        $error = $this->checkHamsterAccess($hamsters);
        if ($error) {
            return $error;
        }

        return $this->json([
            'hamsters' => $hamsters,
        ], Response::HTTP_OK, [], ['groups' => ['hamster_list']]);
    }

    #[Route('/api/hamsters/reproduce', name: 'hamsters_reproduce', methods: ['POST'])]
    public function reproduce(
        Request $request,
        HamstersRepository $hamstersRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $hamster1 = $hamstersRepository->find($data['idHamster1'] ?? 0);
        $hamster2 = $hamstersRepository->find($data['idHamster2'] ?? 0);

        if (!$hamster1 || !$hamster2) {
            return $this->json(['message' => 'Hamsters non trouvés.'], Response::HTTP_NOT_FOUND);
        }

        $error = $this->checkHamsterAccess($hamster1);
        if ($error) {
            return $error;
        }

        $error = $this->checkHamsterAccess($hamster2);
        if ($error) {
            return $error;
        }

        if ($hamster1->getGenre() === $hamster2->getGenre()) {
            return $this->json(
                ['message' => 'Les deux hamsters doivent être de sexe opposé.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$hamster1->isActive() || !$hamster2->isActive()) {
            return $this->json(
                ['message' => 'Les deux hamsters doivent être actifs.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $faker = Factory::create('fr_FR');
        $newHamster = (new Hamsters())
            ->setName($faker->firstName())
            ->setGenre($faker->randomElement(['m', 'f']))
            ->setOwner($hamster1->getOwner());

        $entityManager->persist($newHamster);
        $entityManager->flush();

        return $this->json(['hamster' => $newHamster], Response::HTTP_CREATED, [], ['groups' => ['hamster_list']]);
    }
}
