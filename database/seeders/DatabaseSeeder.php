<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EditionSeeder::class,
            TimeSlotSeeder::class,
            MissionSeeder::class,
            ShiftSeeder::class,
            AdminSeeder::class,
            InvitationCodeSeeder::class,
        ]);

        // Compte benevole de test, documente dans le README.
        if (! User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'edition_id' => Edition::current()?->id,
            ]);
        }
    }
}
