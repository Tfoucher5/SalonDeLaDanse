<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationCode>
 */
class InvitationCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => InvitationCode::generateCode(),
            'edition_id' => Edition::factory(),
            'used_at' => null,
            'user_id' => null,
        ];
    }

    /**
     * Code deja consomme par un compte.
     */
    public function used(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => now(),
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }
}
