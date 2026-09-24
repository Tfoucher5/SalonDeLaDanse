<?php

namespace App\Enums;

/**
 * Le verdict d'un badge scanné à l'entrée.
 *
 * `Inactive` et `NoHolder` concernent un vrai badge qui ne vaut plus ;
 * `Unrecognized` un code que le Salon n'a jamais émis. Les trois refusent
 * l'entrée, mais ne disent pas la même chose à l'équipe.
 */
enum BadgeScanStatus: string
{
    case Valid = 'valid';
    case Inactive = 'inactive';
    case NoHolder = 'no_holder';
    case Unrecognized = 'unrecognized';

    public function title(): string
    {
        return match ($this) {
            self::Valid => 'Badge valable',
            self::Inactive, self::NoHolder => 'Badge non valable',
            self::Unrecognized => 'Badge non reconnu',
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::Valid => 'Bénévole de cette édition.',
            self::Inactive => 'Ce compte n\'est plus bénévole de cette édition.',
            self::NoHolder => 'Le compte rattaché à ce badge n\'existe plus.',
            self::Unrecognized => 'Ce code n\'a pas été émis par le Salon.',
        };
    }

    /**
     * Le ton de `x-ui.alert` : seul un badge valable passe au vert.
     */
    public function tone(): string
    {
        return $this === self::Valid ? 'success' : 'danger';
    }
}
