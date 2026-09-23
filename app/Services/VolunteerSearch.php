<?php

namespace App\Services;

use App\Models\Edition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * La recherche multi-critères du back-office, en un seul endroit.
 *
 * L'écran de liste et les exports interrogent la même requête : un export qui
 * ne rendrait pas exactement les lignes affichées à l'écran serait un piège.
 */
class VolunteerSearch
{
    /**
     * Les bénévoles de l'édition qui satisfont les critères donnés.
     *
     * `withCount` donne le nombre de créneaux sans hydrater la moindre
     * réservation, et le compteur d'attributions forcées épargne une requête
     * par ligne au moment de lire l'état du planning.
     *
     * @param  array{name?: ?string, mission?: ?int, status?: ?string, day?: ?string}  $criteria
     * @return Builder<User>
     */
    public function query(Edition $edition, array $criteria): Builder
    {
        return User::query()
            ->volunteers()
            ->ofEdition($edition)
            ->withCount([
                'assignments',
                'assignments as forced_assignments_count' => fn (Builder $forced) => $forced->where('assigned_by_admin', true),
            ])
            ->when($criteria['name'] ?? null, fn (Builder $query, string $name) => $query->where(
                fn (Builder $named) => $named
                    ->where('first_name', 'like', '%'.$name.'%')
                    ->orWhere('last_name', 'like', '%'.$name.'%')
            ))
            ->when(($criteria['status'] ?? null) === 'validated', fn (Builder $query) => $query->whereNotNull('planning_validated_at'))
            ->when(($criteria['status'] ?? null) === 'pending', fn (Builder $query) => $query->whereNull('planning_validated_at'))
            ->when($criteria['mission'] ?? null, fn (Builder $query, int $mission) => $query->whereHas(
                'shifts', fn (Builder $shift) => $shift->where('mission_id', $mission)
            ))
            ->when($criteria['day'] ?? null, fn (Builder $query, string $day) => $query->whereHas(
                'shifts', fn (Builder $shift) => $shift->where('date', $day)
            ))
            ->orderBy('last_name')
            ->orderBy('first_name');
    }
}
