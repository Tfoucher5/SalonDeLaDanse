<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VolunteerSearchRequest;
use App\Models\Edition;
use App\Services\EditionOverview;
use App\Services\EditionPlanning;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Le planning global : une journée à la fois, et sur chaque créneau le nom de
 * qui y est inscrit.
 *
 * C'est la vue symétrique de la grille du bénévole. Celle-ci ne montre que des
 * places restantes ; celle-là montre les personnes, missions sous restriction
 * comprises. L'écran est en lecture seule : on modifie un planning depuis la
 * fiche du bénévole, où l'on voit le reste de ses créneaux avant de trancher.
 */
class PlanningController extends Controller
{
    public function __construct(
        private readonly EditionPlanning $planning,
        private readonly EditionOverview $overview,
    ) {}

    public function __invoke(VolunteerSearchRequest $request): View
    {
        $edition = Edition::current();
        $criteria = $request->criteria();

        $days = $edition?->days() ?? collect();
        $selectedDay = $this->selectedDay($days, $criteria['day']);

        return view('admin.planning.index', [
            'edition' => $edition,
            'criteria' => $criteria,
            'missions' => $edition?->missions()->get() ?? collect(),
            'days' => $days,
            'selectedDay' => $selectedDay,
            'timeSlots' => $edition?->timeSlots()->get() ?? collect(),
            'shiftsByTimeSlot' => $edition === null || $selectedDay === null
                ? collect()
                : $this->planning->dayByTimeSlot($edition, $selectedDay->toDateString(), $criteria['mission'], $criteria['name']),
            'dayFillRate' => $edition === null || $selectedDay === null
                ? null
                : $this->overview->fillRateByDay($edition)->firstWhere('key', $selectedDay->toDateString()),
        ]);
    }

    /**
     * Le jour affiché : celui demandé s'il fait partie de l'édition, le premier
     * jour sinon. Un paramètre farfelu ne casse pas une page de consultation.
     *
     * @param  Collection<int, Carbon>  $days
     */
    private function selectedDay(Collection $days, ?string $requested): ?Carbon
    {
        if ($requested !== null) {
            $match = $days->first(fn (Carbon $day): bool => $day->toDateString() === $requested);

            if ($match !== null) {
                return $match;
            }
        }

        return $days->first();
    }
}
