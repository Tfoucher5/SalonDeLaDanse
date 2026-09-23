<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Les écritures de l'administrateur sur le compte d'un bénévole.
 *
 * Le profil est verrouillé dès la création : nom, prénom, e-mail, téléphone et
 * photo ne changent plus que par ici. `UserPolicy` dit qui a le droit, ce
 * service dit comment — en particulier pour la photo, qu'un remplacement doit
 * laisser propre sur le disque.
 */
class VolunteerAccount
{
    /**
     * Longueur du mot de passe temporaire. Assez long pour n'être pas
     * devinable, assez court pour se dicter au téléphone.
     */
    private const TEMPORARY_PASSWORD_LENGTH = 12;

    /**
     * Met à jour les informations personnelles verrouillées.
     *
     * @param  array{first_name: string, last_name: string, email: string, phone: string, birth_date: string}  $attributes
     */
    public function updatePersonalInformation(User $volunteer, array $attributes, ?UploadedFile $photo = null): void
    {
        $previousPhoto = $volunteer->photo_path;
        $newPhoto = $photo?->store(config('salon.photo.directory'), 'public');

        DB::transaction(function () use ($volunteer, $attributes, $newPhoto): void {
            $volunteer->fill($attributes);

            if ($newPhoto !== null) {
                $volunteer->photo_path = $newPhoto;
            }

            // Changer l'adresse invalide la vérification : c'est l'identifiant
            // de connexion, la nouvelle doit être confirmée comme la première.
            if ($volunteer->isDirty('email')) {
                $volunteer->email_verified_at = null;
            }

            $volunteer->save();
        });

        // La photo remplacée ne part qu'une fois l'écriture passée : un échec
        // plus haut doit laisser le compte avec l'image qu'il avait.
        if ($newPhoto !== null && filled($previousPhoto)) {
            Storage::disk('public')->delete($previousPhoto);
        }
    }

    /**
     * Réinitialise le mot de passe et rend celui qu'il faut transmettre.
     *
     * Aucun envoi d'e-mail — hors périmètre du MVP : l'administrateur lit le
     * mot de passe à l'écran, une seule fois, et le transmet lui-même. Le jeton
     * « se souvenir de moi » est régénéré dans la foulée, sans quoi une session
     * ouverte ailleurs survivrait à la réinitialisation.
     */
    public function resetPassword(User $volunteer): string
    {
        $password = Str::password(self::TEMPORARY_PASSWORD_LENGTH, symbols: false);

        $volunteer->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        return $password;
    }
}
