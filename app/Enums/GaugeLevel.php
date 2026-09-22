<?php

namespace App\Enums;

use App\Models\Shift;

/**
 * Niveau de remplissage d'un créneau, tel que lu par le bénévole.
 *
 * La couleur seule ne suffit jamais : chaque niveau porte aussi un libellé en
 * toutes lettres, pour que la grille reste lisible en niveaux de gris.
 */
enum GaugeLevel: string
{
    case Free = 'free';
    case Tight = 'tight';
    case Full = 'full';

    /**
     * Un créneau plein n'est pas une erreur : il est gris, jamais rouge.
     */
    public static function for(Shift $shift): self
    {
        $remaining = $shift->remaining_places;

        if ($remaining <= 0) {
            return self::Full;
        }

        $tightRatio = (float) config('salon.gauge.tight_ratio');

        if ($shift->capacity > 0 && $remaining / $shift->capacity <= $tightRatio) {
            return self::Tight;
        }

        return self::Free;
    }

    /**
     * Les places restantes en toutes lettres.
     */
    public function label(int $remainingPlaces): string
    {
        if ($this === self::Full || $remainingPlaces <= 0) {
            return 'Complet';
        }

        return $remainingPlaces.' place'.($remainingPlaces > 1 ? 's' : '').' restante'.($remainingPlaces > 1 ? 's' : '');
    }
}
