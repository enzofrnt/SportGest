<?php

namespace App\Entity;

use App\Repository\SportifRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\NiveauSportif;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: SportifRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('VIEW', object)"
        ),
        new GetCollection(
            security: "is_granted('ROLE_COACH') or is_granted('ROLE_RESPONSABLE') or is_granted('ROLE_ADMIN')"
        ),
        new Post(
            security: "is_granted('ROLE_RESPONSABLE') or is_granted('ROLE_ADMIN')"
        ),
        new Put(
            security: "is_granted('EDIT', object) or is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            security: "is_granted('EDIT', object) or is_granted('ROLE_ADMIN')"
        )
    ],
    normalizationContext: ['groups' => ['sportif:read']],
    denormalizationContext: ['groups' => ['sportif:write']]
)]
class Sportif extends Utilisateur
{
    #[ORM\Column]
    #[Groups(['sportif:read', 'sportif:write'])]
    private ?\DateTimeImmutable $dateInscription = null;

    #[ORM\Column(type: 'string', enumType: NiveauSportif::class)]
    #[Groups(['sportif:read', 'sportif:write'])]
    private ?NiveauSportif $niveauSportif = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(mappedBy: 'sportif', targetEntity: Reservation::class, orphanRemoval: true)]
    #[Groups(['sportif:read', 'sportif:write'])]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getDateInscription(): ?\DateTimeImmutable
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeImmutable $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    public function getNiveauSportif(): ?NiveauSportif
    {
        return $this->niveauSportif;
    }

    public function setNiveauSportif(NiveauSportif $niveauSportif): static
    {
        $this->niveauSportif = $niveauSportif;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setSportif($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getSportif() === $this) {
                $reservation->setSportif(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getNom();
    }
}