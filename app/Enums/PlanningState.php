<?php

namespace App\Enums;

use App\Models\Edition;
use App\Models\User;

/**
 * État du planning d'un bénévole, tel qu'affiché sur son dashboard.
 *
 * Les trois cas du cahier des charges : brouillon modifiable, validé
 * définitivement, ou consultation seule parce que la fenêtre est fermée.
 */
enum PlanningState: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Closed = 'closed';

    /**
     * Un planning déjà validé le reste, fenêtre ouverte ou non : c'est
     * l'information la plus utile au bénévole.
     */
    public static function for(User $user, ?Edition $edition): self
    {
        if ($user->planningIsValidated()) {
            return self::Validated;
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
            self::Closed => 'Inscriptions fermées',
        };
    }

    /**
     * Phrase expliquant l'état, affichée sous le libellé.
     */
    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Vous pouvez encore ajouter ou retirer des créneaux. Pensez à valider définitivement votre planning une fois vos choix arrêtés.',
            self::Validated => 'Votre planning est validé et verrouillé. Contactez l\'équipe organisatrice pour toute modification.',
            self::Closed => 'La composition des plannings est fermée. Le planning reste consultable, mais plus modifiable.',
        };
    }

    /**
     * Motif court expliquant, sur la grille, pourquoi aucun créneau n'est
     * réservable. Null quand le planning est encore modifiable.
     */
    public function blockingReason(): ?string
    {
        return match ($this) {
            self::Draft => null,
            self::Validated => 'Votre planning est validé définitivement.',
            self::Closed => 'Les inscriptions au planning sont fermées.',
        };
    }
}
