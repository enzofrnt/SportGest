<?php

namespace App\Security\Voter;

use App\Entity\Coach;
use App\Entity\Responsable;
use App\Entity\Seance;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SeanceVoter extends Voter
{
    const CREATE = 'CREATE';
    const EDIT = 'EDIT';
    const UPDATE_STATUT = 'UPDATE_STATUT';

    protected function supports(string $attribute, $subject): bool
    {
        // Si l'action est CREATE, on peut retourner true sans vérifier le sujet
        if ($attribute === self::CREATE) {
            return true;
        }

        // Pour les autres actions, vérifier que nous avons une séance
        if (!in_array($attribute, [self::EDIT, self::UPDATE_STATUT])) {
            return false;
        }

        // Vérifier que le sujet est bien une séance
        if (!$subject instanceof Seance) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Les administrateurs peuvent tout faire
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Création, modification et mise à jour du statut réservées aux coachs et responsables
        if (!($user instanceof Coach || $user instanceof Responsable)) {
            return false;
        }

        switch ($attribute) {
            case self::CREATE:
                return true; // Un coach peut créer une séance
            case self::EDIT:
                return $this->canEdit($subject, $user);
            case self::UPDATE_STATUT:
                return $this->canUpdateStatut($subject, $user);
        }

        return false;
    }

    private function canEdit(Seance $seance, UserInterface $user): bool
    {
        // Un coach peut modifier uniquement ses propres séances
        if ($user instanceof Coach) {
            return $seance->getCoach()->getId() === $user->getId();
        }

        // Les responsables peuvent modifier toutes les séances
        return $user instanceof Responsable;
    }

    private function canUpdateStatut(Seance $seance, UserInterface $user): bool
    {
        // Même logique que pour l'édition
        return $this->canEdit($seance, $user);
    }
}
