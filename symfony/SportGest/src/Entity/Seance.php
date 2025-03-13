<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\TypeSeance;
use App\Enum\StatutSeance;
use App\Enum\NiveauSportif;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['seance:read']],
    denormalizationContext: ['groups' => ['seance:write']]
)]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['seance:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['seance:read', 'seance:write'])]
    private ?\DateTimeInterface $dateHeure = null;

    #[ORM\Column(type: 'string', enumType: TypeSeance::class)]
    #[Groups(['seance:read', 'seance:write'])]
    private ?TypeSeance $typeSeance = null;

    #[ORM\Column(length: 255)]
    #[Groups(['seance:read', 'seance:write'])]
    private ?string $themeSeance = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['seance:read', 'seance:write'])]
    private ?Coach $coach = null;

    /**
     * @var Collection<int, Sportif>
     */
    #[ORM\ManyToMany(targetEntity: Sportif::class)]
    #[Groups(['seance:read', 'seance:write'])]
    private Collection $sportifs;

    #[ORM\Column(type: 'string', enumType: StatutSeance::class)]
    #[Groups(['seance:read', 'seance:write'])]
    private ?StatutSeance $statut = null;

    #[ORM\Column(type: 'string', enumType: NiveauSportif::class)]
    #[Groups(['seance:read', 'seance:write'])]
    private ?NiveauSportif $niveauSeance = null;

    /**
     * @var Collection<int, Exercice>
     */
    #[ORM\ManyToMany(targetEntity: Exercice::class)]
    #[Groups(['seance:read', 'seance:write'])]
    private Collection $exercices;

    public function __construct()
    {
        $this->sportifs = new ArrayCollection();
        $this->exercices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateHeure(): ?\DateTimeInterface
    {
        return $this->dateHeure;
    }

    public function setDateHeure(\DateTimeInterface $dateHeure): static
    {
        $this->dateHeure = $dateHeure;

        return $this;
    }

    public function getTypeSeance(): ?TypeSeance
    {
        return $this->typeSeance;
    }

    public function setTypeSeance(TypeSeance $typeSeance): static
    {
        $this->typeSeance = $typeSeance;

        return $this;
    }

    public function getThemeSeance(): ?string
    {
        return $this->themeSeance;
    }

    public function setThemeSeance(string $themeSeance): static
    {
        $this->themeSeance = $themeSeance;

        return $this;
    }

    public function getCoach(): ?Coach
    {
        return $this->coach;
    }

    public function setCoach(?Coach $coach): static
    {
        $this->coach = $coach;

        return $this;
    }

    /**
     * @return Collection<int, Sportif>
     */
    public function getSportifs(): Collection
    {
        return $this->sportifs;
    }

    public function addSportif(Sportif $sportif): static
    {
        if (!$this->sportifs->contains($sportif)) {
            $this->sportifs->add($sportif);
        }

        return $this;
    }

    public function removeSportif(Sportif $sportif): static
    {
        $this->sportifs->removeElement($sportif);

        return $this;
    }

    public function getStatut(): ?StatutSeance
    {
        return $this->statut;
    }

    public function setStatut(StatutSeance $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getNiveauSeance(): ?NiveauSportif
    {
        return $this->niveauSeance;
    }

    public function setNiveauSeance(NiveauSportif $niveauSeance): static
    {
        $this->niveauSeance = $niveauSeance;

        return $this;
    }

    /**
     * @return Collection<int, Exercice>
     */
    public function getExercices(): Collection
    {
        return $this->exercices;
    }

    public function addExercice(Exercice $exercice): static
    {
        if (!$this->exercices->contains($exercice)) {
            $this->exercices->add($exercice);
        }

        return $this;
    }

    public function removeExercice(Exercice $exercice): static
    {
        $this->exercices->removeElement($exercice);

        return $this;
    }
}