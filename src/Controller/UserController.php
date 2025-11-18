<?php

namespace App\Controller;

use App\Entity\Hamsters;
use App\Entity\User;
use App\Repository\UserRepository;
use Faker\Factory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(
                ['message' => 'Email et mot de passe sont requis.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($data['password']);
        $user->setRoles(['ROLE_USER']);
        $user->setGold(500);

        $errors = $validator->validate($user);

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

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);

        foreach ($this->createStarterHamsters($user) as $hamster) {
            $entityManager->persist($hamster);
        }

        $entityManager->flush();

        return $this->json(
            [
                'message' => 'Utilisateur créé avec succès.',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                ],
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route('/api/user/{id}', name: 'api_get_user', methods: ['GET'])]
    public function getUserById(User $user): JsonResponse
    {
        return $this->json([
            'user' => $user,
        ], Response::HTTP_OK);
    }

    #[Route('/api/delete/{id}', name: 'api_delete_user', methods: ['DELETE'])]
    public function delete(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(
                ['message' => 'Accès refusé. Seuls les administrateurs peuvent supprimer des utilisateurs.'],
                Response::HTTP_FORBIDDEN
            );
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(
                ['message' => 'Utilisateur non trouvé.'],
                Response::HTTP_NOT_FOUND
            );
        }

        foreach ($user->getHamsters() as $hamster) {
            $entityManager->remove($hamster);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(
            [
                'message' => 'Utilisateur et tous ses hamsters associés ont été supprimés avec succès.',
                'deleted_user_id' => $id,
                'deleted_user_email' => $user->getEmail(),
            ],
            Response::HTTP_OK
        );
    }

    private function createStarterHamsters(User $owner): array
    {
        $faker = Factory::create('fr_FR');
        $genders = ['f', 'm', 'f', 'm'];
        $hamsters = [];

        foreach ($genders as $genre) {
            $hamster = new Hamsters();
            $hamster->setName($faker->firstName());
            $hamster->setGenre($genre);
            $hamster->setOwner($owner);
            $hamsters[] = $hamster;
        }

        return $hamsters;
    }
}
