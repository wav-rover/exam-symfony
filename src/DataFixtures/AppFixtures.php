<?php

namespace App\DataFixtures;

use App\Entity\Hamsters;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $admin = $this->createAdmin();
        $manager->persist($admin);

        $manager->flush();
    }

    public function createHamsters()
    {
        $faker = \Faker\Factory::create('fr_FR');

        $hamster = new Hamsters();

        $hamster->setName($faker->firstName());
        $hamster->setHunger($faker->numberBetween(0, 100));
        $hamster->setAge($faker->numberBetween(0, 5));
        $hamster->setGenre($faker->randomElement(['m', 'f']));
        $hamster->setActive(true);

        return $hamster;
    }

    public function createAdmin()
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'motdepasse'));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setGold(999999);
        return $user;
    }
}
