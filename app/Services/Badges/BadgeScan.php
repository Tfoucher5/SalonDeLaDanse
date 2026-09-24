<?php

namespace App\Services\Badges;

use App\Enums\BadgeScanStatus;
use App\Models\Edition;
use App\Models\User;

/**
 * Ce qu'un scan a trouvé : le verdict, et le compte et l'édition visés quand
 * le code en désigne un.
 */
readonly class BadgeScan
{
    public function __construct(
        public BadgeScanStatus $status,
        public ?User $volunteer = null,
        public ?Edition $edition = null,
    ) {}
}
