<?php

namespace Database\Factories;

use App\Models\Edition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Edition>
 */
class EditionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Salon de la Danse 2027',
            'starts_on' => '2027-05-14',
            'ends_on' => '2027-05-16',
            'registration_opens_at' => null,
            'registration_closes_at' => null,
            'is_locked' => false,
            'min_slots_per_volunteer' => 1,
            'max_slots_per_volunteer' => 3,
            'is_active' => true,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_locked' => true,
        ]);
    }

    public function registrationClosed(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_opens_at' => now()->subMonth(),
            'registration_closes_at' => now()->subDay(),
        ]);
    }
}
