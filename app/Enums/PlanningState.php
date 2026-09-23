<?php

namespace App\Enums;

use App\Models\Edition;
use App\Models\User;

/**
 * État du planning d'un bénévole, tel qu'affiché sur son dashboard.
 *
 * Brouillon modifiable, validé définitivement, verrouillé par l'équipe
 * organisatrice, ou consultation seule parce que la fenêtre est fermée. Seul
 * le brouillon se modifie.
 */
enum PlanningState: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Locked = 'locked';
    case Closed = 'closed';

    /**
     * Un planning déjà validé le reste, fenêtre ouverte ou non : c'est
     * l'information la plus utile au bénévole. Une attribution par l'équipe
     * organisatrice verrouille de la même façon, sans passer par le bénévole.
     */
    public static function for(User $user, ?Edition $edition): self
    {
        if ($user->planningIsValidated()) {
            return self::Validated;
        }

        if ($user->planningIsLockedByAdmin()) {
            return self::Locked;
        }

        if ($edition === null || ! $edition->registrationIsOpen()) {
            return self::Closed;
        }

        return self::Draft;
    }

    /**
     * Le bénévole peut-il encore composer son planning ?
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Libellé affichable dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Validated => 'Validé',
            self::Locked => 'Verrouillé',
            self::Closed => 'Inscriptions fermées',
        };
    }

    /**
     * Ton du badge qui porte l'état, au sens du design system.
     *
     * Aucun état n'est rouge : un planning fermé ou verrouillé est une
     * situation normale, le rouge reste réservé à ce qui a échoué.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'plum',
            self::Validated => 'free',
            self::Locked => 'neutral',
            self::Closed => 'full',
        };
    }

    /**
     * Phrase expliquant l'état, affichée sous le libellé.
     */
    public function description(): string
    {
        return match ($this) {
            self::Draft => "Vous pouvez encore ajouter ou retirer des créneaux. L'équipe organisatrice validera votre planning avant le Salon.",
            self::Validated => 'Votre planning est validé et verrouillé. Contactez l\'équipe organisatrice pour toute modification.',
            self::Locked => "L'équipe organisatrice vous a attribué un poste : votre planning est verrouillé. Contactez-la pour toute modification.",
            self::Closed => 'La composition des plannings est fermée. Le planning reste consultable, mais plus modifiable.',
        };
    }
}
