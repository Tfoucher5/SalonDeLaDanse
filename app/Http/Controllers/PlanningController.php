<?php

namespace App\Http\Controllers;

use App\Enums\PlanningState;
use App\Models\Edition;
use App\Models\Shift;
use App\Models\User;
use App\Services\PlanningRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanningController extends Controller
{
    public function __construct(private readonly PlanningRules $rules) {}

    /**
     * La grille des créneaux, jour par jour.
     *
     * La confidentialité tient à la requête elle-même, qui ne compte que les
     * réservations et ne charge jamais les bénévoles d'un créneau.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $edition = $user->activeEdition();
        $state = PlanningState::for($user, $edition);

        $days = $edition?->days() ?? collect();
        $selectedDay = $this->selectedDay($days, $request->query('day'));

        $bookedShiftIds = $user->assignments()->pluck('shift_id');

        $shiftsByTimeSlot = $edition === null || $selectedDay === null
            ? collect()
            : $this->shiftsOfDay($edition, $selectedDay, $bookedShiftIds);

        return view('planning.index', [
            'edition' => $edition,
            'state' => $state,
            'days' => $days,
            'selectedDay' => $selectedDay,
            'timeSlots' => $edition?->timeSlots()->get() ?? collect(),
            'shiftsByTimeSlot' => $shiftsByTimeSlot,
            'bookedShiftIds' => $bookedShiftIds,
            'motives' => $this->motives($user, $shiftsByTimeSlot),
        ]);
    }

    /**
     * Le jour affiché : celui demandé s'il fait partie de l'édition, le premier
     * jour sinon. Un paramètre farfelu ne casse pas une page de consultation.
     *
     * @param  Collection<int, Carbon>  $days
     */
    private function selectedDay(Collection $days, mixed $requested): ?Carbon
    {
        if (is_string($requested)) {
            $match = $days->first(fn (Carbon $day): bool => $day->toDateString() === $requested);

            if ($match !== null) {
                return $match;
            }
        }

        return $days->first();
    }

    /**
     * Les créneaux réservables d'une journée, groupés par tranche horaire.
     *
     * Les missions sous restriction et les missions fermées sont écartées par
     * la requête, pas masquées à l'affichage. `withCount` donne la jauge sans hydrater la moindre
     * réservation : un bénévole ne doit connaître que le nombre de places.
     *
     * @return Collection<int, Collection<int, Shift>>
     */
    private function shiftsOfDay(Edition $edition, Carbon $day, Collection $bookedShiftIds): Collection
    {
        return $edition->shifts()
            ->where(fn (Builder $query) => $query
                ->whereHas('mission', fn (Builder $mission) => $mission->bookable())
                // Une mission fermee apres coup ne doit pas faire disparaitre le
                // creneau de celui qui l occupe deja : il le verrait s evaporer
                // de la grille sans explication.
                ->orWhereIn('id', $bookedShiftIds))
            ->where('date', $day->toDateString())
            ->with(['mission:id,name,position,is_public,is_active', 'timeSlot:id,starts_at,ends_at,position'])
            ->withCount('assignments')
            ->get()
            ->sortBy(fn (Shift $shift): int => $shift->mission->position)
            ->groupBy('time_slot_id');
    }

    /**
     * Le motif de blocage de chaque créneau affiché, indexé par identifiant.
     *
     * Les créneaux déjà retenus sont chargés une seule fois pour toute la
     * grille : c'est ce qui évite une requête par carte.
     *
     * @param  Collection<int, Collection<int, Shift>>  $shiftsByTimeSlot
     * @return array<int, string|null>
     */
    private function motives(User $user, Collection $shiftsByTimeSlot): array
    {
        $bookedShifts = $this->rules->bookedShifts($user);
        $edition = $user->activeEdition();
        $motives = [];

        foreach ($shiftsByTimeSlot->flatten() as $shift) {
            $motives[$shift->id] = $this->rules
                ->violationFor($user, $shift, $bookedShifts)
                ?->message($edition);
        }

        return $motives;
    }
}
