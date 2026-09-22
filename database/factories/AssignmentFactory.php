<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shift_id' => Shift::factory(),
            'assigned_by_admin' => false,
        ];
    }

    public function forcedByAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_by_admin' => true,
        ]);
    }
}
