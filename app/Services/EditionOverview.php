<?php

namespace App\Services;

use App\Enums\GaugeLevel;
use App\Models\Edition;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Les compteurs du back-office, calculés en un seul endroit.
 *
 * Un chiffre faux est pire que pas de chiffre : tout se compte par requête sur
 * `assignments`, jamais sur un compteur dénormalisé. Les missions sous
 * restriction sont comprises — l'administrateur supervise tout le dispositif,
 * pas seulement ce que voit un bénévole.
 */
class EditionOverview
{
    /**
     * Les créneaux déjà chargés, par édition.
     *
     * Le dashboard interroge trois axes de suite : sans ce cache d'instance,
     * la même requête partirait trois fois.
     *
     * @var array<int, Collection<int, Shift>>
     */
    private array $countedShifts = [];

    /**
     * Le dispositif humain : bénévoles attendus, comptes créés, plannings figés.
     *
     * « Attendus » compte les codes d'invitation émis, puisque le recrutement se
     * fait hors plateforme : un code égale un bénévole retenu.
     *
     * @return array{expected: int, accounts: int, validated: int, pending: int, codes_left: int}
     */
    public function headcount(Edition $edition): array
    {
        $expected = $edition->invitationCodes()->count();
        $accounts = $this->volunteersOf($edition)->count();
        $validated = $this->volunteersOf($edition)->whereNotNull('planning_validated_at')->count();

        return [
            'expected' => $expected,
            'accounts' => $accounts,
            'validated' => $validated,
            'pending' => $accounts - $validated,
            'codes_left' => $edition->invitationCodes()->whereNull('used_at')->count(),
        ];
    }

    /**
     * Le taux de remplissage jour par jour.
     *
     * @return Collection<int, array{key: string, label: string, capacity: int, taken: int, remaining: int, rate: int, level: string, restricted: bool}>
     */
    public function fillRateByDay(Edition $edition): Collection
    {
        return $this->countedShifts($edition)
            ->groupBy(fn (Shift $shift): string => $shift->date->toDateString())
            ->map(fn (Collection $shifts, string $date): array => $this->aggregate(
                $date,
                ucfirst($shifts->first()->date->translatedFormat('l j F')),
                $shifts,
            ))
            ->sortKeys()
            ->values();
    }

    /**
     * Le taux de remplissage mission par mission, dans l'ordre d'affichage.
     *
     * @return Collection<int, array{key: string, label: string, capacity: int, taken: int, remaining: int, rate: int, level: string, restricted: bool}>
     */
    public function fillRateByMission(Edition $edition): Collection
    {
        return $this->countedShifts($edition)
            ->groupBy('mission_id')
            ->map(fn (Collection $shifts): array => $this->aggregate(
                (string) $shifts->first()->mission_id,
                $shifts->first()->mission->name,
                $shifts,
                restricted: ! $shifts->first()->mission->is_public,
            ))
            ->sortBy('label')
            ->values();
    }

    /**
     * Le taux de remplissage de l'édition entière.
     *
     * @return array{key: string, label: string, capacity: int, taken: int, remaining: int, rate: int, level: string, restricted: bool}
     */
    public function fillRate(Edition $edition): array
    {
        return $this->aggregate('edition', $edition->name, $this->countedShifts($edition));
    }

    /**
     * Les créneaux de l'édition, jauge comptée par requête.
     *
     * Chargés une seule fois puis regroupés en mémoire : 165 créneaux tiennent
     * largement, et une agrégation SQL par axe multiplierait les requêtes.
     *
     * @return Collection<int, Shift>
     */
    private function countedShifts(Edition $edition): Collection
    {
        return $this->countedShifts[$edition->getKey()] ??= $edition->shifts()
            ->with('mission:id,name,is_public,is_active,position')
            ->withCount('assignments')
            ->get();
    }

    /**
     * Places prises sur places offertes, et le pourcentage qui va avec.
     *
     * @param  Collection<int, Shift>  $shifts
     * @return array{key: string, label: string, capacity: int, taken: int, remaining: int, rate: int, level: string, restricted: bool}
     */
    private function aggregate(string $key, string $label, Collection $shifts, bool $restricted = false): array
    {
        $capacity = (int) $shifts->sum('capacity');
        $taken = (int) $shifts->sum('assignments_count');
        $remaining = max(0, $capacity - $taken);

        return [
            'key' => $key,
            'label' => $label,
            'capacity' => $capacity,
            'taken' => $taken,
            'remaining' => $remaining,
            'rate' => $capacity > 0 ? (int) round($taken / $capacity * 100) : 0,
            // Le back-office lit la meme echelle que le benevole : un axe
            // sature est gris, un axe qui manque de monde est vert.
            'level' => GaugeLevel::fromRemaining($remaining, $capacity)->value,
            'restricted' => $restricted,
        ];
    }

    /**
     * Les bénévoles rattachés à l'édition.
     *
     * Les comptes créés avant le rattachement n'ont pas d'édition propre : ils
     * retombent sur l'édition courante, comme partout ailleurs dans l'application.
     *
     * @return Builder<User>
     */
    private function volunteersOf(Edition $edition): Builder
    {
        return User::query()->volunteers()->ofEdition($edition);
    }
}
