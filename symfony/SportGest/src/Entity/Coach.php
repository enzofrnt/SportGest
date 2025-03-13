<?php

namespace App\Entity;

use App\Repository\CoachRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CoachRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['coach:read']],
    denormalizationContext: ['groups' => ['coach:write']]
)]
class Coach extends Utilisateur
{

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['coach:read', 'coach:write'])]
    private array $specialites = [];

    #[ORM\Column]
    #[Groups(['coach:read', 'coach:write'])]
    private ?float $tarifHoraire = null;

    public function getSpecialites(): array
    {
        return $this->specialites;
    }

    public function setSpecialites(array $specialites): static
    {
        $this->specialites = $specialites;

        return $this;
    }
    public function getTarifHoraire(): ?float
    {
        return $this->tarifHoraire;
    }

    public function setTarifHoraire(float $tarifHoraire): static
    {
        $this->tarifHoraire = $tarifHoraire;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getNom() . ' ' . $this->getPrenom();
    }
}