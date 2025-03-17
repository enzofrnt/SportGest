<?php

namespace App\Entity;

use App\Repository\CoachRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[ORM\Column]
    #[Groups(['coach:read', 'coach:write'])]
    private ?float $tarifHoraire = null;

    /**
     * @var Collection<int, Specialite>
     */
    #[ORM\ManyToMany(targetEntity: Specialite::class)]
    private Collection $specialite;

    public function __construct()
    {
        $this->specialite = new ArrayCollection();
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

    /**
     * @return Collection<int, Specialite>
     */
    public function getSpecialite(): Collection
    {
        return $this->specialite;
    }

    public function addSpecialite(Specialite $specialite): static
    {
        if (!$this->specialite->contains($specialite)) {
            $this->specialite->add($specialite);
        }

        return $this;
    }

    public function removeSpecialite(Specialite $specialite): static
    {
        $this->specialite->removeElement($specialite);

        return $this;
    }
}