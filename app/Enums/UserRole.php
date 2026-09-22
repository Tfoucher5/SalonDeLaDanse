<?php

namespace App\Enums;

enum UserRole: string
{
    case Volunteer = 'volunteer';
    case Admin = 'admin';

    /**
     * Libellé affichable dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Volunteer => 'Bénévole',
            self::Admin => 'Administrateur',
        };
    }
}
