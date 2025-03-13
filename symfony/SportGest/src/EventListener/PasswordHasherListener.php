<?php

namespace App\EventListener;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEntityListener(event: Events::prePersist, entity: Utilisateur::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Utilisateur::class)]
class PasswordHasherListener
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function prePersist(Utilisateur $utilisateur, LifecycleEventArgs $args): void
    {
        $this->hashPassword($utilisateur);
    }

    public function preUpdate(Utilisateur $utilisateur, LifecycleEventArgs $args): void
    {
        $this->hashPassword($utilisateur);
    }

    private function hashPassword(Utilisateur $utilisateur): void
    {
        // Si le mot de passe est vide, ne rien faire
        if (!$utilisateur->getPassword()) {
            return;
        }

        // On vérifie si le mot de passe est déjà haché
        // Cette vérification est simple et peut être améliorée
        if (strlen($utilisateur->getPassword()) < 60) {
            // Le mot de passe n'est pas haché, on le hache
            $hashedPassword = $this->passwordHasher->hashPassword(
                $utilisateur,
                $utilisateur->getPassword()
            );
            $utilisateur->setPassword($hashedPassword);
        }
    }
}