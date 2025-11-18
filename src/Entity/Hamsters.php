<?php

namespace App\Entity;

use App\Repository\HamstersRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HamstersRepository::class)]
class Hamsters
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(
        min: 2,
        minMessage: 'Le nom doit contenir au moins {{ min }} caractères'
    )]
    private ?string $name = null;

    #[ORM\Column(options: ['default' => 100])]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: 'La valeur de hunger doit être entre {{ min }} et {{ max }}'
    )]
    private int $hunger = 100;

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\Range(
        min: 0,
        max: 500,
        notInRangeMessage: 'L\'âge doit être entre {{ min }} et {{ max }} jours'
    )]
    private int $age = 0;

    #[ORM\Column(length: 1)]
    #[Assert\NotBlank(message: 'Le genre est obligatoire')]
    #[Assert\Choice(
        choices: ['m', 'f'],
        message: 'Le genre doit être "m" ou "f"'
    )]
    private ?string $genre = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'hamsters')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le propriétaire est obligatoire')]
    #[Assert\Valid]
    private User $owner;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getHunger(): int
    {
        return $this->hunger;
    }

    public function setHunger(int $hunger): static
    {
        $this->hunger = $hunger;
        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): static
    {
        $this->genre = $genre;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
