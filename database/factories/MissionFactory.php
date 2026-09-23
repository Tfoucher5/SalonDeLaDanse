<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Mission>
 */
class MissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'edition_id' => Edition::factory(),
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name),
            'is_public' => true,
            'is_active' => true,
            'default_capacity' => config('salon.seed.default_shift_capacity'),
            'instructions' => null,
            'position' => fake()->numberBetween(1, 20),
        ];
    }

    /**
     * Mission sous restriction : hors planning public.
     */
    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Mission fermee : plus proposee, mais ses inscrits gardent leur poste.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
