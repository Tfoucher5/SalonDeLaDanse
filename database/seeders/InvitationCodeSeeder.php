<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\InvitationCode;
use Illuminate\Database\Seeder;

class InvitationCodeSeeder extends Seeder
{
    /**
     * Un lot de codes d invitation disponibles, pour les tests manuels.
     *
     * Un code est consomme une seule fois : c est ce qui garantit une personne,
     * un compte.
     */
    public function run(): void
    {
        $edition = Edition::current();

        if ($edition === null) {
            return;
        }

        $target = config('salon.seed.invitation_codes');
        $missing = $target - $edition->invitationCodes()->count();

        for ($i = 0; $i < $missing; $i++) {
            do {
                $code = InvitationCode::generateCode();
            } while (InvitationCode::query()->where('code', $code)->exists());

            $edition->invitationCodes()->create(['code' => $code]);
        }
    }
}
