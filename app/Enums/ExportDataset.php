<?php

namespace App\Enums;

/**
 * Les trois sorties de données du back-office.
 *
 * Chacune répond à un usage précis le jour du Salon : le planning général pour
 * la coordination, la liste par mission pour le responsable de poste, les
 * fiches contact pour joindre quelqu'un dans l'urgence.
 */
enum ExportDataset: string
{
    case Planning = 'planning';
    case Missions = 'missions';
    case Contacts = 'contacts';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning général',
            self::Missions => 'Liste par mission',
            self::Contacts => 'Fiches contact',
        };
    }

    /**
     * Ce que la feuille contient, et ce qu'on en fait.
     */
    public function description(): string
    {
        return match ($this) {
            self::Planning => "Une ligne par créneau attribué, dans l'ordre chronologique : jour, tranche horaire, mission, bénévole et coordonnées.",
            self::Missions => 'Une ligne par créneau ouvert, mission par mission, avec la jauge et les bénévoles qui y sont inscrits. Les créneaux vides y figurent : ce sont les trous à combler.',
            self::Contacts => 'Une ligne par bénévole : coordonnées, nombre de créneaux et état du planning.',
        };
    }

    /**
     * Les critères de recherche qui s'appliquent à cette feuille.
     *
     * La liste par mission est centrée sur le créneau, pas sur le bénévole :
     * filtrer par nom ou par statut de validation y ferait disparaître des
     * créneaux vides, c'est-à-dire exactement ce qu'elle sert à montrer.
     *
     * @return array<int, string>
     */
    public function filters(): array
    {
        return match ($this) {
            self::Missions => ['mission', 'day'],
            default => ['name', 'mission', 'status', 'day'],
        };
    }

    /**
     * Nom de l'onglet du classeur. Excel refuse au-delà de 31 caractères.
     */
    public function sheetTitle(): string
    {
        return $this->label();
    }

    /**
     * Le morceau de nom de fichier qui dit de quelle feuille il s'agit.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Planning => 'planning-general',
            self::Missions => 'liste-par-mission',
            self::Contacts => 'fiches-contact',
        };
    }
}
