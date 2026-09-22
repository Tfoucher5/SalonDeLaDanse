<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Compte administrateur de developpement.
     *
     * Les identifiants viennent de .env via config/salon.php. Rien en dur ici :
     * sans configuration, le seeder previent et ne cree aucun compte.
     */
    public function run(): void
    {
        $email = config('salon.admin.email');
        $password = config('salon.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn(
                'AdminSeeder ignore : renseignez SALON_ADMIN_EMAIL et SALON_ADMIN_PASSWORD dans .env.'
            );

            return;
        }

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'first_name' => config('salon.admin.first_name'),
            'last_name' => config('salon.admin.last_name'),
            'phone' => (string) config('salon.admin.phone'),
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'profile_locked_at' => $user->profile_locked_at ?? now(),
            'edition_id' => Edition::current()?->id,
        ])->save();
    }
}
