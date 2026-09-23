<?php

namespace App\Enums;

/**
 * Niveau de pourvoi d'un créneau, d'une mission ou d'une journée, tel que lu
 * par l'équipe organisatrice.
 *
 * C'est l'inverse de `GaugeLevel` : pour le bénévole, beaucoup de places
 * libres est une bonne nouvelle (vert) ; pour l'organisation, c'est un poste
 * à pourvoir (rouge). Les deux échelles ne se mélangent jamais sur un écran.
 */
enum StaffingLevel: string
{
    case Staffed = 'staffed';
    case Partial = 'partial';
    case Critical = 'critical';

    /**
     * Sous ce taux de remplissage, le poste est jugé critique.
     */
    public const CRITICAL_BELOW = 0.5;

    public static function fromCounts(int $taken, int $capacity): self
    {
        if ($capacity <= 0 || $taken >= $capacity) {
            return self::Staffed;
        }

        return $taken / $capacity < self::CRITICAL_BELOW ? self::Critical : self::Partial;
    }

    /**
     * Le planning d'un bénévole au regard de son quota : sous le minimum il
     * manque quelqu'un quelque part, au maximum il donne tout ce qu'il peut.
     */
    public static function forQuota(int $count, int $minimum, int $maximum): self
    {
        return match (true) {
            $count < $minimum => self::Critical,
            $count >= $maximum => self::Staffed,
            default => self::Partial,
        };
    }

    /**
     * Le niveau en toutes lettres : la couleur ne fait que confirmer.
     */
    public function label(): string
    {
        return match ($this) {
            self::Staffed => 'Complet',
            self::Partial => 'En cours',
            self::Critical => 'À pourvoir',
        };
    }

    /**
     * Ton du badge et de la jauge, au sens du design system.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Staffed => 'free',
            self::Partial => 'tight',
            self::Critical => 'danger',
        };
    }

    /**
     * Remplissage d'une barre de progression. Ces classes sont compilees par
     * Tailwind, qui lit aussi `app/Enums` (voir tailwind.config.js).
     */
    public function barClass(): string
    {
        return match ($this) {
            self::Staffed => 'bg-gauge-free',
            self::Partial => 'bg-gauge-tight',
            self::Critical => 'bg-danger/70',
        };
    }

    /**
     * Trait d'un anneau de progression SVG.
     */
    public function strokeClass(): string
    {
        return match ($this) {
            self::Staffed => 'stroke-gauge-free',
            self::Partial => 'stroke-gauge-tight',
            self::Critical => 'stroke-danger',
        };
    }

    /**
     * Couleur d'un chiffre ou d'un libelle qui porte le niveau.
     */
    public function textClass(): string
    {
        return match ($this) {
            self::Staffed => 'text-gauge-free',
            self::Partial => 'text-gauge-tight',
            self::Critical => 'text-danger',
        };
    }
}
