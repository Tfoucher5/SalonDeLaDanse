<?php

namespace App\Enums;

use App\Models\Edition;

/**
 * Les règles qui peuvent refuser une écriture du planning.
 *
 * Réserver, retirer et figer un planning passent toutes par cette même liste :
 * un planning fermé l'est pour les trois. Chaque cas porte son propre message :
 * une règle qui bloque doit toujours se justifier à l'écran. « Indisponible »
 * n'apprend rien au bénévole, « vous avez déjà retenu vos 3 créneaux » lui dit
 * quoi faire.
 */
enum BookingRule: string
{
    case PlanningClosed = 'planning_closed';
    case PlanningValidated = 'planning_validated';
    case PlanningLockedByAdmin = 'planning_locked_by_admin';
    case AlreadyBooked = 'already_booked';
    case NotBooked = 'not_booked';
    case OutsideEdition = 'outside_edition';
    case RestrictedMission = 'restricted_mission';
    case MaxSlotsReached = 'max_slots_reached';
    case OverlappingSlot = 'overlapping_slot';
    case ThreeConsecutiveSlots = 'three_consecutive_slots';
    case ShiftFull = 'shift_full';
    case MinimumSlotsNotReached = 'minimum_slots_not_reached';

    /**
     * Le motif affiché au bénévole.
     *
     * Le quota se lit sur l'édition : il est paramétrable, il n'est jamais écrit
     * en dur dans une phrase.
     */
    public function message(?Edition $edition = null): string
    {
        return match ($this) {
            self::PlanningClosed => 'Les inscriptions au planning sont fermées.',
            self::PlanningValidated => 'Votre planning est validé définitivement.',
            self::PlanningLockedByAdmin => "Votre planning est verrouillé : l'équipe organisatrice vous a attribué un poste.",
            self::AlreadyBooked => 'Ce créneau fait déjà partie de votre planning.',
            self::NotBooked => 'Ce créneau ne fait pas partie de votre planning.',
            self::OutsideEdition => "Ce créneau n'appartient pas à l'édition en cours.",
            self::RestrictedMission => "Cette mission est attribuée uniquement par l'équipe organisatrice.",
            self::MaxSlotsReached => $edition === null
                ? 'Vous avez déjà retenu le nombre maximum de créneaux.'
                : "Vous avez déjà retenu vos {$edition->max_slots_per_volunteer} créneaux. Retirez-en un pour en choisir un autre.",
            self::OverlappingSlot => 'Vous êtes déjà inscrit sur une autre mission de cette tranche horaire.',
            self::ThreeConsecutiveSlots => 'Vous ne pouvez pas enchaîner trois tranches horaires consécutives le même jour : une pause est obligatoire.',
            self::ShiftFull => 'Toutes les places de ce créneau sont prises.',
            self::MinimumSlotsNotReached => $edition === null
                ? 'Ce planning ne compte pas assez de créneaux pour être validé.'
                : "Ce planning doit compter au moins {$edition->min_slots_per_volunteer} créneau"
                    .($edition->min_slots_per_volunteer > 1 ? 'x' : '').' pour être validé.',
        };
    }
}
