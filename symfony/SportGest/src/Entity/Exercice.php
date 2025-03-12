<?php

namespace App\Entity;

use App\Repository\ExerciceRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\DifficulteExercice;

#[ORM\Entity(repositoryClass: ExerciceRepository::class)]
class Exercice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $dureeEstimee = null;

    #[ORM\Column(type: 'string', enumType: DifficulteExercice::class)]
    private ?DifficulteExercice $difficulte = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDureeEstimee(): ?int
    {
        return $this->dureeEstimee;
    }

    public function setDureeEstimee(int $dureeEstimee): static
    {
        $this->dureeEstimee = $dureeEstimee;

        return $this;
    }

    public function getDifficulte(): ?DifficulteExercice
    {
        return $this->difficulte;
    }

    public function setDifficulte(DifficulteExercice $difficulte): static
    {
        $this->difficulte = $difficulte;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getNom();
    }
}
