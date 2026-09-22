<?php

namespace Database\Seeders;

use App\Models\Edition;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    /**
     * Les 5 tranches horaires, identiques sur les trois jours.
     *
     * La premiere dure 1h30 et non 2h : les heures reelles sont stockees, aucune
     * duree n est codee en dur.
     *
     * @var array<int, array{int, string, string}>
     */
    private const SLOTS = [
        [1, '08:30', '10:00'],
        [2, '10:00', '12:00'],
        [3, '12:00', '14:00'],
        [4, '14:00', '16:00'],
        [5, '16:00', '18:00'],
    ];

    public function run(): void
    {
        $edition = Edition::current();

        if ($edition === null) {
            return;
        }

        foreach (self::SLOTS as [$position, $startsAt, $endsAt]) {
            $edition->timeSlots()->updateOrCreate(
                ['position' => $position],
                ['starts_at' => $startsAt, 'ends_at' => $endsAt],
            );
        }
    }
}
