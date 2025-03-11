<?php

namespace App\Entity;

use App\Repository\SportifRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SportifRepository::class)]
class Sportif extends Utilisateur
{
    #[ORM\Column]
    private ?\DateTimeImmutable $dateInscription = null;

    #[ORM\Column(length: 255)]
    private ?string $niveauSportif = null;

    public function getDateInscription(): ?\DateTimeImmutable
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeImmutable $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    public function getNiveauSportif(): ?string
    {
        return $this->niveauSportif;
    }

    public function setNiveauSportif(string $niveauSportif): static
    {
        $this->niveauSportif = $niveauSportif;

        return $this;
    }
}
