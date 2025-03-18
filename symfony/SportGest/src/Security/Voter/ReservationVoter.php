<?php

namespace App\Security\Voter;

use App\Entity\Coach;
use App\Entity\Responsable;
use App\Entity\Sportif;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ReservationVoter extends Voter
{
    const CREATE = 'CREATE_RESERVATION';
    const CANCEL = 'CANCEL_RESERVATION';
    const VIEW = 'VIEW_RESERVATIONS';

    protected function supports(string $attribute, $subject): bool
    {
        // Vérifier si l'attribut est supporté
        return in_array($attribute, [self::CREATE, self::CANCEL, self::VIEW]);
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

        // Les coachs peuvent voir les réservations mais pas en créer/annuler
        if ($user instanceof Coach) {
            return $attribute === self::VIEW;
        }

        // Les responsables peuvent voir et annuler des réservations mais pas en créer
        if ($user instanceof Responsable) {
            return $attribute === self::VIEW || $attribute === self::CANCEL;
        }

        // Seuls les sportifs peuvent créer des réservations
        if ($user instanceof Sportif) {
            switch ($attribute) {
                case self::CREATE:
                    return true;
                case self::CANCEL:
                    // Un sportif peut annuler ses propres réservations
                    // Le sujet ici serait l'ID du sportif pour lequel on annule
                    if (is_numeric($subject)) {
                        return $user->getId() === (int) $subject;
                    }
                    return true; // Pas de sujet, on suppose que c'est pour lui-même
                case self::VIEW:
                    // Un sportif peut voir ses propres réservations
                    // Le sujet ici serait l'ID du sportif dont on consulte les réservations
                    if (is_numeric($subject)) {
                        return $user->getId() === (int) $subject;
                    }
                    return true; // Pas de sujet, on suppose que c'est pour lui-même
            }
        }

        return false;
    }
}