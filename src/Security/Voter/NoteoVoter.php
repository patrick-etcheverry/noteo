<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class NoteoVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, ['OPTIONS_APPLICATION'])
            && $subject instanceof \App\Entity\Enseignant;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        $accesAutorise = false;

        switch ($attribute) {
            case 'OPTIONS_APPLICATION' :
                //Il faut être admin pour pouvoir modifier l'application
                $accesAutorise = in_array("ROLE_ADMIN", $user->getRoles());
                break;
        }

        return $accesAutorise;
    }
}
