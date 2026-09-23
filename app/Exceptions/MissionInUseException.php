<?php

namespace App\Exceptions;

use App\Models\Mission;
use RuntimeException;

/**
 * Une mission ne peut pas être supprimée : des bénévoles y sont inscrits.
 *
 * Supprimer emporterait les créneaux, donc les inscriptions, sans que personne
 * ne soit prévenu. La sortie est de désactiver la mission — elle disparaît de
 * la grille, mais ceux qui y sont gardent leur poste.
 */
class MissionInUseException extends RuntimeException
{
    private function __construct(public readonly Mission $mission, public readonly int $assigned, string $message)
    {
        parent::__construct($message);
    }

    public static function make(Mission $mission, int $assigned): self
    {
        return new self($mission, $assigned, sprintf(
            '« %s » ne peut pas être supprimée : %d créneau%s y %s déjà attribué%s. '
                .'Retirez ces attributions, ou désactivez la mission pour la fermer sans rien perdre.',
            $mission->name,
            $assigned,
            $assigned > 1 ? 'x' : '',
            $assigned > 1 ? 'sont' : 'est',
            $assigned > 1 ? 's' : '',
        ));
    }
}
