<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\Mission;
use App\Models\Shift;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            // Mission et tranche sont rattachees a la meme edition que le creneau.
            'mission_id' => fn (array $attributes) => Mission::factory()
                ->create(['edition_id' => $attributes['edition_id']])->id,
            'time_slot_id' => fn (array $attributes) => TimeSlot::factory()
                ->create(['edition_id' => $attributes['edition_id']])->id,
            'date' => '2027-05-14',
            'capacity' => config('salon.seed.default_shift_capacity'),
        ];
    }

    public function withCapacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => $capacity,
        ]);
    }
}
