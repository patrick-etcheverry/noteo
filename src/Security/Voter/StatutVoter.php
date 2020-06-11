<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class StatutVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, ['STATUT_EDIT', 'STATUT_DELETE'])
            && $subject instanceof \App\Entity\Statut;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }
        $accesAutorise = false;
        switch ($attribute) {
            case 'STATUT_EDIT':
            case 'STATUT_DELETE':
                //Cette fonctionnalité est dispo si l'utilisateur est admin ou que l'enseignant modifie/supprime son propre statut
                $accesAutorise = in_array("ROLE_ADMIN", $user->getRoles()) || $subject->getEnseignant()->getId() == $user->getId();
                break;
        }
        return $accesAutorise;
    }
}
