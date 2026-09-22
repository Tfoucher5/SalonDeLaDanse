<?php

namespace Database\Seeders;

use App\Models\Edition;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Le produit cartesien missions x jours x tranches.
     *
     * 9 missions publiques x 3 jours x 5 tranches = 135 creneaux reservables,
     * plus 30 creneaux sur les missions sous restriction.
     */
    public function run(): void
    {
        $edition = Edition::current();

        if ($edition === null) {
            return;
        }

        $capacity = config('salon.seed.default_shift_capacity');
        $timeSlots = $edition->timeSlots()->get();
        $days = $edition->days();

        foreach ($edition->missions()->get() as $mission) {
            foreach ($days as $day) {
                foreach ($timeSlots as $timeSlot) {
                    $edition->shifts()->updateOrCreate(
                        [
                            'mission_id' => $mission->id,
                            'time_slot_id' => $timeSlot->id,
                            'date' => $day->toDateString(),
                        ],
                        ['capacity' => $capacity],
                    );
                }
            }
        }
    }
}
