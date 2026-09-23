<?php

namespace Database\Seeders;

use App\Exceptions\BookingRuleException;
use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\Shift;
use App\Models\User;
use App\Services\PlanningRules;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class VolunteerSeeder extends Seeder
{
    /**
     * Domaine des comptes fictifs : il les distingue des vrais comptes et
     * rend le seeder rejouable sans doublons.
     */
    public const EMAIL_DOMAIN = 'benevoles.test';

    /**
     * Des benevoles fictifs et leurs plannings, pour tester l'application et
     * le back-office avec des jauges vraiment remplies.
     *
     * Rien n'est ecrit a la main dans la table des affectations : chaque
     * creneau passe par PlanningRules::book(), la meme porte que le benevole.
     * Quota maximum, chevauchements, trois tranches consecutives, missions
     * restreintes et jauges pleines sont donc respectes par construction.
     *
     * Appele par DatabaseSeeder en local ; seul : `php artisan db:seed --class=VolunteerSeeder`.
     */
    public function run(PlanningRules $rules): void
    {
        if (app()->isProduction()) {
            $this->command?->error('VolunteerSeeder refuse de tourner en production.');

            return;
        }

        $edition = Edition::current();

        if ($edition === null || ! $edition->registrationIsOpen()) {
            $this->command?->warn('VolunteerSeeder ignore : aucune edition ouverte aux inscriptions.');

            return;
        }

        $missing = config('salon.seed.volunteers') - User::query()
            ->where('email', 'like', '%@'.self::EMAIL_DOMAIN)
            ->count();

        if ($missing <= 0) {
            $this->command?->info('VolunteerSeeder : les '.config('salon.seed.volunteers').' benevoles fictifs existent deja, rien a ajouter.');

            return;
        }

        // `onBookableMissions` remplace `onPublicMissions` : une mission
        // desactivee n est plus proposee, le seeder ne doit pas y inscrire
        // quelqu un que les regles refuseraient ensuite.
        $shifts = $edition->shifts()->onBookableMissions()->with('mission')->get();

        for ($i = 0; $i < $missing; $i++) {
            $volunteer = $this->createVolunteer($edition);

            $booked = $this->bookRandomShifts($rules, $volunteer, $shifts, $this->targetSlots($edition));

            // Une partie des plannings est figee, comme le ferait l'equipe
            // organisatrice : de quoi tester les deux etats dans l'admin.
            if ($booked >= $edition->min_slots_per_volunteer && fake()->boolean(35)) {
                $rules->validate($volunteer);
            }
        }

        $this->command?->info("{$missing} benevoles fictifs crees (mot de passe : password).");
    }

    /**
     * Un benevole tel qu'il sort de l'inscription : compte rattache a
     * l'edition, profil verrouille, code d'invitation consomme.
     */
    private function createVolunteer(Edition $edition): User
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        $volunteer = User::factory()->forEdition($edition)->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => str($firstName.'.'.$lastName)->ascii()->lower()->replaceMatches('/[^a-z.]/', '')
                .'.'.fake()->unique()->numberBetween(100, 9999).'@'.self::EMAIL_DOMAIN,
        ]);

        InvitationCode::factory()->used($volunteer)->create(['edition_id' => $edition->id]);

        return $volunteer;
    }

    /**
     * Combien de creneaux ce benevole cherche a retenir : parfois aucun (un
     * planning vide reste un brouillon valide), jamais plus que le quota.
     */
    private function targetSlots(Edition $edition): int
    {
        if (fake()->boolean(10)) {
            return 0;
        }

        return fake()->numberBetween(1, $edition->max_slots_per_volunteer);
    }

    /**
     * Tente des creneaux au hasard jusqu'a en retenir `$target`. Un refus des
     * regles n'est pas une erreur : on passe simplement au suivant.
     *
     * @param  Collection<int, Shift>  $shifts
     */
    private function bookRandomShifts(PlanningRules $rules, User $volunteer, Collection $shifts, int $target): int
    {
        $booked = 0;

        foreach ($shifts->shuffle() as $shift) {
            if ($booked >= $target) {
                break;
            }

            try {
                $rules->book($volunteer, $shift);
                $booked++;
            } catch (BookingRuleException) {
                continue;
            }
        }

        return $booked;
    }
}
