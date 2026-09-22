<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Le code d invitation n existe pas, ou il a deja servi a creer un compte.
 */
class InvitationCodeUnavailableException extends RuntimeException
{
    public static function make(): self
    {
        return new self("Ce code d'invitation n'est pas valide ou a déjà été utilisé.");
    }
}
