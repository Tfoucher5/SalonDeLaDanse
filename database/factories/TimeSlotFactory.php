<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            // Positions distinctes par defaut : (edition_id, position) est unique.
            'position' => fake()->unique()->numberBetween(1, 250),
        ];
    }

    public function atPosition(int $position, string $startsAt, string $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
