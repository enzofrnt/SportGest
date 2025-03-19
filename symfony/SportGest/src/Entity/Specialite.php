<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SpecialiteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SpecialiteRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['specialite:read']],
    denormalizationContext: ['groups' => ['specialite:write']]
)]
class Specialite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['specialite:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['specialite:read', 'specialite:write'])]
    private ?string $nom = null;

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

    public function __toString(): string
    {
        return $this->nom;
    }
}
