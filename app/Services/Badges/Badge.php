<?php

namespace App\Services\Badges;

/**
 * Ce qu'imprime un badge, calculé une fois et passé tel quel à la vue PDF.
 *
 * La photo et le QR code arrivent en data URI : dompdf ne charge aucune
 * ressource distante, et la vue n'a rien à résoudre elle-même.
 */
readonly class Badge
{
    public function __construct(
        public int $volunteerId,
        public string $identifier,
        public string $firstName,
        public string $lastName,
        public string $initials,
        public string $editionName,
        public string $verificationUrl,
        public string $qrCode,
        public ?string $photo,
    ) {}

    public function hasPhoto(): bool
    {
        return $this->photo !== null;
    }
}
