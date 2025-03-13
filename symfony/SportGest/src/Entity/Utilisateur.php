<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'role', type: 'string')]
#[ORM\DiscriminatorMap(['utilisateur' => Utilisateur::class, 'coach' => Coach::class, 'sportif' => Sportif::class, 'responsable' => Responsable::class])]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['coach:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['coach:read', 'coach:write'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['coach:read', 'coach:write'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['coach:read', 'coach:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(['coach:write'])]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(['coach:read'])]
    private array $roles = [];

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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        // Rôle de base pour tous les utilisateurs
        $roles = ['ROLE_USER'];

        // Attribution des rôles selon le type d'utilisateur
        if ($this instanceof Coach) {
            $roles[] = 'ROLE_COACH';
        } elseif ($this instanceof Responsable) {
            $roles[] = 'ROLE_RESPONSABLE';
        }

        foreach ($this->roles as $role) {
            $roles[] = $role;
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données sensibles temporaires
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }


    public function getRole(): string
    {
        $class = get_class($this);
        return match ($class) {
            Sportif::class => 'Sportif',
            Coach::class => 'Coach',
            Responsable::class => 'Responsable',
            default => 'Utilisateur',
        };
    }
}