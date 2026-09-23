<?php

namespace App\Services;

use App\Models\Edition;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Le planning de l'édition vu par l'organisation : les créneaux, et qui les
 * occupe.
 *
 * C'est l'exact inverse de la grille du bénévole, qui ne connaît qu'un nombre
 * de places restantes. Ici les noms circulent — la confidentialité protège les
 * bénévoles entre eux, pas de l'équipe — et les missions sous restriction sont
 * comprises.
 *
 * L'écran de planning et l'export « liste par mission » lisent tous deux cette
 * classe : une feuille qui ne dirait pas la même chose que l'écran serait un
 * piège.
 */
class EditionPlanning
{
    /**
     * Les créneaux de l'édition, bénévoles chargés en une seule requête.
     *
     * @return Collection<int, Shift>
     */
    public function shifts(Edition $edition, ?int $missionId = null, ?string $day = null): Collection
    {
        return $edition->shifts()
            ->with([
                'mission:id,name,is_public,is_active,instructions,position',
                'timeSlot:id,starts_at,ends_at,position',
                'volunteers:id,first_name,last_name,photo_path',
            ])
            ->when($missionId, fn (Builder $query, int $mission) => $query->where('mission_id', $mission))
            ->when($day, fn (Builder $query, string $date) => $query->where('date', $date))
            ->get();
    }

    /**
     * Une journée du Salon, tranche horaire par tranche horaire.
     *
     * Les missions sont rendues dans leur ordre d'affichage, pour que deux
     * tranches se lisent l'une sous l'autre dans le même ordre.
     *
     * @return Collection<int, Collection<int, Shift>>
     */
    public function dayByTimeSlot(Edition $edition, string $day, ?int $missionId = null): Collection
    {
        return $this->shifts($edition, $missionId, $day)
            ->sortBy(fn (Shift $shift): int => $shift->mission->position)
            ->groupBy('time_slot_id');
    }
}
