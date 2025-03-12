<?php

namespace App\Entity;

use App\Repository\SportifRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\NiveauSportif;

#[ORM\Entity(repositoryClass: SportifRepository::class)]
class Sportif extends Utilisateur
{
    #[ORM\Column]
    private ?\DateTimeImmutable $dateInscription = null;

    #[ORM\Column(type: 'string', enumType: NiveauSportif::class)]
    private ?NiveauSportif $niveauSportif = null;

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

    public function __toString(): string
    {
        return $this->getNom();
    }
}
