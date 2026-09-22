<?php

namespace Database\Seeders;

use App\Models\Edition;
use Illuminate\Database\Seeder;

class EditionSeeder extends Seeder
{
    /**
     * L edition courante du MVP. Le CRUD multi-editions est hors perimetre.
     */
    public function run(): void
    {
        Edition::query()->updateOrCreate(
            ['name' => 'Salon de la Danse 2027'],
            [
                'starts_on' => '2027-05-14',
                'ends_on' => '2027-05-16',
                'registration_opens_at' => null,
                'registration_closes_at' => null,
                'is_locked' => false,
                'min_slots_per_volunteer' => 1,
                'max_slots_per_volunteer' => 3,
                'is_active' => true,
            ],
        );
    }
}
