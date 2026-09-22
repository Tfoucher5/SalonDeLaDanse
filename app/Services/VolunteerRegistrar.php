<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\InvitationCodeUnavailableException;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Creation d un compte benevole a partir d un code d invitation.
 *
 * Un code est consomme une seule fois : c est ce qui garantit une personne, un
 * compte. La consommation et la creation vivent donc dans la meme transaction,
 * avec verrou sur la ligne du code pour resister a deux envois simultanes.
 */
class VolunteerRegistrar
{
    /**
     * @param  array{first_name: string, last_name: string, email: string, phone: string, password: string}  $attributes
     *
     * @throws InvitationCodeUnavailableException
     */
    public function register(string $code, array $attributes, UploadedFile $photo): User
    {
        $photoPath = $photo->store(config('salon.photo.directory'), 'public');

        try {
            return DB::transaction(function () use ($code, $attributes, $photoPath): User {
                $invitation = $this->lockAvailableCode($code);

                $user = new User;
                $user->fill($attributes);
                $user->forceFill([
                    'photo_path' => $photoPath,
                    'role' => UserRole::Volunteer,
                    'edition_id' => $invitation->edition_id,
                    // Le profil est verrouille des la creation : seul un
                    // administrateur pourra encore modifier ces informations.
                    'profile_locked_at' => now(),
                ])->save();

                $invitation->forceFill([
                    'used_at' => now(),
                    'user_id' => $user->id,
                ])->save();

                return $user;
            });
        } catch (Throwable $exception) {
            // Pas de compte, pas de photo orpheline sur le disque.
            Storage::disk('public')->delete($photoPath);

            throw $exception;
        }
    }

    /**
     * Le code existe-t-il et reste-t-il disponible ?
     *
     * Verification sans verrou, pour les ecrans en amont du formulaire.
     */
    public function codeIsAvailable(string $code): bool
    {
        return InvitationCode::query()
            ->where('code', $code)
            ->whereNull('used_at')
            ->exists();
    }

    /**
     * @throws InvitationCodeUnavailableException
     */
    private function lockAvailableCode(string $code): InvitationCode
    {
        $invitation = InvitationCode::query()
            ->where('code', $code)
            ->lockForUpdate()
            ->first();

        if ($invitation === null || $invitation->isUsed()) {
            throw InvitationCodeUnavailableException::make();
        }

        return $invitation;
    }
}
