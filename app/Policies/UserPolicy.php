<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Qui peut modifier prenom, nom, e-mail, telephone et photo ?
     *
     * Le profil est verrouille des la creation du compte : passe ce point, seul
     * un administrateur peut encore y toucher.
     */
    public function updatePersonalInformation(User $actor, User $target): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return $actor->is($target) && ! $target->profileIsLocked();
    }
}
