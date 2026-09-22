<?php

namespace App\Http\Controllers;

use App\Enums\PlanningState;
use App\Models\Edition;
use App\Models\Shift;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanningController extends Controller
{
    /**
     * La grille des créneaux, jour par jour.
     *
     * Lecture seule : aucune réservation n'est possible depuis cet écran. La
     * confidentialité tient à la requête elle-même, qui ne compte que les
     * réservations et ne charge jamais les bénévoles d'un créneau.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $edition = $user->activeEdition();
        $state = PlanningState::for($user, $edition);

        $days = $edition?->days() ?? collect();
        $selectedDay = $this->selectedDay($days, $request->query('day'));

        return view('planning.index', [
            'edition' => $edition,
            'state' => $state,
            'blockingReason' => $state->blockingReason(),
            'days' => $days,
            'selectedDay' => $selectedDay,
            'timeSlots' => $edition?->timeSlots()->get() ?? collect(),
            'shiftsByTimeSlot' => $edition === null || $selectedDay === null
                ? collect()
                : $this->shiftsOfDay($edition, $selectedDay),
            'bookedShiftIds' => $user->assignments()->pluck('shift_id'),
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
     * Les créneaux publics d'une journée, groupés par tranche horaire.
     *
     * Les missions sous restriction sont écartées par la requête, pas masquées
     * à l'affichage. `withCount` donne la jauge sans hydrater la moindre
     * réservation : un bénévole ne doit connaître que le nombre de places.
     *
     * @return Collection<int, Collection<int, Shift>>
     */
    private function shiftsOfDay(Edition $edition, Carbon $day): Collection
    {
        return $edition->shifts()
            ->onPublicMissions()
            ->where('date', $day->toDateString())
            ->with(['mission:id,name,position', 'timeSlot:id,starts_at,ends_at,position'])
            ->withCount('assignments')
            ->get()
            ->sortBy(fn (Shift $shift): int => $shift->mission->position)
            ->groupBy('time_slot_id');
    }
}
