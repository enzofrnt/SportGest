<?php

namespace App\Security\Voter;

use App\Entity\Coach;
use App\Entity\Responsable;
use App\Entity\Sportif;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SportifVoter extends Voter
{
    const VIEW = 'VIEW';
    const EDIT = 'EDIT';

    protected function supports(string $attribute, $subject): bool
    {
        // Si ce n'est pas l'un des attributs que nous gérons, retourner false
        if (!in_array($attribute, [self::VIEW, self::EDIT])) {
            return false;
        }

        // Vérifier que le sujet est bien un sportif
        if (!$subject instanceof Sportif) {
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

        /** @var Sportif $sportif */
        $sportif = $subject;

        // Les coachs, responsables et admins peuvent tout voir
        if ($user instanceof Coach || $user instanceof Responsable || in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Les sportifs ne peuvent voir que leurs propres informations
        if ($user instanceof Sportif) {
            switch ($attribute) {
                case self::VIEW:
                    return $this->canView($sportif, $user);
                case self::EDIT:
                    return $this->canEdit($sportif, $user);
            }
        }

        return false;
    }

    private function canView(Sportif $sportif, Sportif $user): bool
    {
        // Un sportif peut voir uniquement ses propres informations
        return $sportif->getId() === $user->getId();
    }

    private function canEdit(Sportif $sportif, Sportif $user): bool
    {
        // Un sportif peut modifier uniquement ses propres informations
        return $sportif->getId() === $user->getId();
    }
}
