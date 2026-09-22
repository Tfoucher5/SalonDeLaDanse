<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('06########'),
            'photo_path' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Volunteer,
            // Le profil est verrouille des la creation du compte.
            'profile_locked_at' => now(),
            'planning_validated_at' => null,
            'edition_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    /**
     * Benevole rattache a une edition.
     */
    public function forEdition(?Edition $edition = null): static
    {
        return $this->state(fn (array $attributes) => [
            'edition_id' => $edition?->id ?? Edition::factory(),
        ]);
    }

    /**
     * Planning valide definitivement, donc verrouille.
     */
    public function validatedPlanning(): static
    {
        return $this->state(fn (array $attributes) => [
            'planning_validated_at' => now(),
        ]);
    }
}
