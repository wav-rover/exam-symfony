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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    #[Route('/api/hamsters/{id}/sell', name: 'hamsters_sell', methods: ['POST'])]
    public function sell(
        Hamsters $hamsters,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $error = $this->checkHamsterAccess($hamsters);
        if ($error) {
            return $error;
        }

        $owner = $hamsters->getOwner();
        $currentGold = $owner->getGold() ?? 0;
        $owner->setGold($currentGold + 300);

        $entityManager->remove($hamsters);
        $entityManager->flush();

        return $this->json(
            [
                'message' => 'Hamster vendu avec succès pour 300 gold.',
                'gold' => $owner->getGold(),
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/api/hamsters/{id}/rename', name: 'hamsters_rename', methods: ['PUT'])]
    public function rename(
        Hamsters $hamsters,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $error = $this->checkHamsterAccess($hamsters);
        if ($error) {
            return $error;
        }

        $data = json_decode($request->getContent(), true);
        $newName = $data['name'] ?? null;

        $hamsters->setName(trim($newName));

        $errors = $validator->validate($hamsters);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(
                ['message' => 'Erreurs de validation', 'errors' => $errorMessages],
                Response::HTTP_BAD_REQUEST
            );
        }

        $entityManager->flush();

        return $this->json(
            [
                'message' => 'Hamster renommé avec succès.',
                'hamster' => $hamsters,
            ],
            Response::HTTP_OK,
            [],
            ['groups' => ['hamster_list']]
        );
    }
}
