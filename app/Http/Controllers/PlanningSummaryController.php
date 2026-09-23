<?php

namespace App\Http\Controllers;

use App\Enums\PlanningState;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PlanningSummaryController extends Controller
{
    /**
     * La fiche récapitulative du bénévole : ses missions, ses horaires et les
     * consignes qui vont avec.
     *
     * C'est la page qu'on imprime et qu'on emporte le jour J : la mise en page
     * papier passe par `@media print`, aucune génération de PDF.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $edition = $user->activeEdition();

        return view('planning.summary', [
            'user' => $user,
            'edition' => $edition,
            'state' => PlanningState::for($user, $edition),
            'shiftsByDay' => $this->shiftsByDay($user),
        ]);
    }

    /**
     * Les créneaux retenus, groupés par jour puis ordonnés dans la journée.
     *
     * Seuls les créneaux du bénévole connecté sont chargés : la fiche ne dit
     * jamais qui d'autre est inscrit sur la même mission.
     *
     * @return Collection<string, Collection<int, Shift>>
     */
    private function shiftsByDay(User $user): Collection
    {
        return $user->shifts()
            ->with(['mission:id,name,instructions,position,is_public,is_active', 'timeSlot:id,starts_at,ends_at,position'])
            ->get()
            ->sortBy(fn (Shift $shift): string => $shift->date->toDateString().'#'.str_pad(
                (string) $shift->timeSlot->position, 2, '0', STR_PAD_LEFT
            ))
            ->groupBy(fn (Shift $shift): string => $shift->date->toDateString());
    }
}
