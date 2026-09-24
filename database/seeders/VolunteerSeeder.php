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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VolunteerSeeder extends Seeder
{
    /**
     * Domaine des comptes fictifs : il les distingue des vrais comptes et
     * rend le seeder rejouable sans doublons.
     */
    public const EMAIL_DOMAIN = 'benevoles.test';

    /**
     * Attrait de chaque mission publique, dans l'ordre de `position` : les
     * benevoles se ruent sur les premieres et boudent les dernieres. Sans ce
     * biais, un tirage uniforme remplit toutes les missions au meme taux et
     * le back-office n'affiche qu'une seule couleur de jauge.
     *
     * @var array<int, float>
     */
    private const MISSION_POPULARITY = [12, 9, 6, 4, 2.5, 1.5, 1, 0.5, 0.25];

    /**
     * Attrait de chaque jour de l'edition : le samedi fait le plein, le
     * dimanche (demontage) peine a recruter.
     *
     * @var array<int, float>
     */
    private const DAY_POPULARITY = [1, 2, 0.6];

    /**
     * Nombre de missions, parmi les plus prisees, que le seeder complete
     * jusqu'a la derniere place : le hasard seul laisse toujours un creneau
     * a moitie vide, et le back-office doit aussi montrer une jauge pleine.
     */
    private const COMPLETE_MISSIONS = 2;

    /**
     * Part des places d'une mission restreinte attribuee d'office par
     * l'equipe organisatrice, dans l'ordre de `position` : l'une bien
     * avancee, l'autre encore vide.
     *
     * @var array<int, float>
     */
    private const RESTRICTED_FILL = [0.7, 0];

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
        $weights = $this->shiftWeights($edition, $shifts);
        $drafts = collect();

        for ($i = 0; $i < $missing; $i++) {
            $volunteer = $this->createVolunteer($edition);

            $booked = $this->bookRandomShifts($rules, $volunteer, $shifts, $weights, $this->targetSlots($edition));

            // Une partie des plannings est figee, comme le ferait l'equipe
            // organisatrice : de quoi tester les deux etats dans l'admin.
            if ($booked >= $edition->min_slots_per_volunteer && fake()->boolean(55)) {
                $rules->validate($volunteer);
            } else {
                $drafts->push($volunteer);
            }
        }

        $this->completePopularMissions($rules, $shifts, $drafts);
        $this->assignRestrictedShifts($rules, $edition, $drafts);

        $this->command?->info("{$missing} benevoles fictifs crees (mot de passe : password).");
    }

    /**
     * Le poids de tirage de chaque creneau : attrait de sa mission multiplie
     * par celui de son jour.
     *
     * @param  Collection<int, Shift>  $shifts
     * @return array<int, float> poids indexes par identifiant de creneau
     */
    private function shiftWeights(Edition $edition, Collection $shifts): array
    {
        $missionRanks = $shifts->pluck('mission')->unique('id')->sortBy('position')->pluck('id')->flip();
        $dayRanks = $edition->days()->map->toDateString()->flip();

        return $shifts->mapWithKeys(fn (Shift $shift): array => [
            $shift->id => (self::MISSION_POPULARITY[$missionRanks[$shift->mission_id]] ?? 1)
                * (self::DAY_POPULARITY[$dayRanks[$shift->date->toDateString()] ?? 0] ?? 1),
        ])->all();
    }

    /**
     * Complete les missions les plus prisees avec les benevoles encore en
     * brouillon, par la meme porte que le tirage : les regles decident.
     *
     * @param  Collection<int, Shift>  $shifts
     * @param  SupportCollection<int, User>  $drafts
     */
    private function completePopularMissions(PlanningRules $rules, Collection $shifts, SupportCollection $drafts): void
    {
        $popular = $shifts->pluck('mission')->unique('id')->sortBy('position')->take(self::COMPLETE_MISSIONS)->pluck('id');

        foreach ($shifts->whereIn('mission_id', $popular) as $shift) {
            foreach ($drafts->shuffle() as $volunteer) {
                if ($shift->isFull()) {
                    break;
                }

                try {
                    $rules->book($volunteer, $shift);
                } catch (BookingRuleException) {
                    continue;
                }
            }
        }
    }

    /**
     * Les missions restreintes ne se reservent pas : l'equipe organisatrice y
     * place elle-meme des benevoles encore en brouillon. Chaque attribution
     * verrouille le planning du benevole, comme dans le back-office.
     *
     * Le benevole choisi n'a rien d'autre ce jour-la et n'a pas atteint son
     * quota : l'attribution forcee ne contourne ainsi que la restriction.
     *
     * @param  SupportCollection<int, User>  $drafts
     */
    private function assignRestrictedShifts(PlanningRules $rules, Edition $edition, SupportCollection $drafts): void
    {
        $restricted = $edition->missions()->where('is_public', false)->orderBy('position')->get();

        foreach ($restricted as $rank => $mission) {
            $fill = self::RESTRICTED_FILL[$rank] ?? 0;

            foreach ($edition->shifts()->where('mission_id', $mission->id)->get() as $shift) {
                $wanted = (int) round($shift->capacity * $fill);

                foreach ($drafts->shuffle() as $volunteer) {
                    if ($shift->assignments()->count() >= $wanted) {
                        break;
                    }

                    $booked = $rules->bookedShifts($volunteer);

                    if ($booked->count() >= $edition->max_slots_per_volunteer
                        || $booked->contains(fn (Shift $retained): bool => $retained->date->isSameDay($shift->date))) {
                        continue;
                    }

                    $rules->assignAsAdmin($volunteer, $shift);
                }
            }
        }
    }

    /**
     * Un benevole tel qu'il sort de l'inscription : compte rattache a
     * l'edition, profil verrouille, code d'invitation consomme.
     */
    private function createVolunteer(Edition $edition): User
    {
        $gender = fake()->randomElement(['male', 'female']);
        $firstName = fake()->firstName($gender);
        $lastName = fake()->lastName();

        $volunteer = User::factory()->forEdition($edition)->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => str($firstName.'.'.$lastName)->ascii()->lower()->replaceMatches('/[^a-z.]/', '')
                .'.'.fake()->unique()->numberBetween(100, 9999).'@'.self::EMAIL_DOMAIN,
            'photo_path' => $this->fakePhoto($gender),
        ]);

        InvitationCode::factory()->used($volunteer)->create(['edition_id' => $edition->id]);

        return $volunteer;
    }

    /**
     * Une photo de profil fictive, rangee comme une vraie photo d'inscription
     * (disque public, dossier des photos benevoles).
     *
     * Les portraits viennent de randomuser.me, service de donnees de test :
     * sans reseau, le benevole est simplement cree sans photo, et ses
     * initiales prennent le relais a l'ecran.
     */
    private function fakePhoto(string $gender): ?string
    {
        $url = sprintf(
            'https://randomuser.me/api/portraits/%s/%d.jpg',
            $gender === 'female' ? 'women' : 'men',
            fake()->numberBetween(0, 99),
        );

        try {
            // IPv4 impose : sur certains postes, la route IPv6 expire sans repondre.
            $response = Http::withOptions(['force_ip_resolve' => 'v4'])->timeout(5)->get($url);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            return null;
        }

        $path = config('salon.photo.directory').'/'.Str::uuid().'.jpg';

        Storage::disk('public')->put($path, $response->body());

        return $path;
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
     * Tente des creneaux jusqu'a en retenir `$target`, dans un ordre tire au
     * hasard mais pondere par l'attrait du creneau (tirage d'Efraimidis et
     * Spirakis). Un refus des regles n'est pas une erreur : on passe
     * simplement au suivant, et un creneau pris deborde sur les autres.
     *
     * @param  Collection<int, Shift>  $shifts
     * @param  array<int, float>  $weights
     */
    private function bookRandomShifts(PlanningRules $rules, User $volunteer, Collection $shifts, array $weights, int $target): int
    {
        $booked = 0;
        $ordered = $shifts->sortByDesc(fn (Shift $shift): float => (mt_rand(1, mt_getrandmax()) / mt_getrandmax()) ** (1 / $weights[$shift->id]));

        foreach ($ordered as $shift) {
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
