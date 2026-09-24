<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlanningState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVolunteerRequest;
use App\Http\Requests\Admin\VolunteerSearchRequest;
use App\Models\Edition;
use App\Models\Shift;
use App\Models\User;
use App\Services\Badges\BadgePrinter;
use App\Services\VolunteerAccount;
use App\Services\VolunteerSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

/**
 * Les bénévoles vus par l'organisation : la liste cherchable, la fiche complète
 * de l'un d'eux, et la modification de ses informations verrouillées.
 *
 * C'est le seul endroit de l'application où les noms circulent : la règle de
 * confidentialité protège les bénévoles entre eux, pas de l'équipe.
 */
class VolunteerController extends Controller
{
    public function __construct(
        private readonly VolunteerSearch $search,
        private readonly VolunteerAccount $accounts,
        private readonly BadgePrinter $badges,
    ) {}

    /**
     * La recherche multi-critères : nom, mission, statut de validation, jour.
     */
    public function index(VolunteerSearchRequest $request): View
    {
        $edition = Edition::current();
        $criteria = $request->criteria();

        return view('admin.volunteers.index', [
            'edition' => $edition,
            'criteria' => $criteria,
            'missions' => $edition?->missions()->get() ?? collect(),
            'days' => $edition?->days() ?? collect(),
            'volunteers' => $edition === null
                ? null
                : $this->search->query($edition, $criteria)->paginate(25)->withQueryString(),
        ]);
    }

    /**
     * La fiche d'un bénévole : ses informations, son planning complet, et les
     * leviers de l'administrateur sur les deux.
     *
     * Les missions sous restriction y figurent, contrairement à la grille
     * publique : l'administrateur voit tout ce qu'il a attribué.
     */
    public function show(User $volunteer): View
    {
        $edition = $volunteer->activeEdition();
        $shiftsByDay = $this->shiftsByDay($volunteer);
        $currentEdition = Edition::current();

        return view('admin.volunteers.show', [
            'volunteer' => $volunteer,
            'edition' => $edition,
            'state' => PlanningState::for($volunteer, $edition),
            'shiftsByDay' => $shiftsByDay,
            'assignableShifts' => $this->assignableShifts($edition, $volunteer),
            // Le bouton du badge ne s'affiche que s'il mène à un PDF : un
            // compte hors de l'édition courante n'en a pas.
            'hasBadge' => $currentEdition !== null && $this->badges->isActiveVolunteer($volunteer, $currentEdition),
        ]);
    }

    /**
     * Le formulaire de modification des informations verrouillées.
     */
    public function edit(User $volunteer): View
    {
        return view('admin.volunteers.edit', [
            'volunteer' => $volunteer,
            'edition' => $volunteer->activeEdition(),
        ]);
    }

    /**
     * L'écriture. Le verrou du profil ne vaut pas pour l'administrateur : c'est
     * `UserPolicy` qui l'autorise, depuis le `FormRequest`.
     */
    public function update(UpdateVolunteerRequest $request, User $volunteer): RedirectResponse
    {
        $this->accounts->updatePersonalInformation(
            $volunteer,
            $request->personalInformation(),
            $request->photo(),
        );

        return to_route('admin.volunteers.show', $volunteer)
            ->with('status', 'Informations personnelles mises à jour.');
    }

    /**
     * Les créneaux du bénévole, groupés par jour puis ordonnés dans la journée.
     *
     * @return Collection<string, Collection<int, Shift>>
     */
    private function shiftsByDay(User $volunteer): Collection
    {
        return $volunteer->shifts()
            ->with(['mission:id,name,is_public,is_active,instructions,position', 'timeSlot:id,starts_at,ends_at,position'])
            ->get()
            ->sortBy(fn (Shift $shift): string => $shift->date->toDateString().'#'.str_pad(
                (string) $shift->timeSlot->position, 2, '0', STR_PAD_LEFT
            ))
            ->groupBy(fn (Shift $shift): string => $shift->date->toDateString());
    }

    /**
     * Les créneaux que l'administrateur peut encore attribuer à ce bénévole.
     *
     * Tous ceux de l'édition, missions restreintes comprises, moins ceux qu'il
     * occupe déjà — le seul refus que l'administrateur ne peut pas forcer. Les
     * créneaux complets restent proposés : dépasser une jauge est précisément
     * une décision d'organisation.
     *
     * @return Collection<string, Collection<int, Shift>>
     */
    private function assignableShifts(?Edition $edition, User $volunteer): Collection
    {
        if ($edition === null) {
            return collect();
        }

        return $edition->shifts()
            ->with(['mission:id,name,is_public,is_active,position', 'timeSlot:id,starts_at,ends_at,position'])
            ->withCount('assignments')
            ->whereNotIn('id', $volunteer->assignments()->pluck('shift_id'))
            ->get()
            ->sortBy(fn (Shift $shift): string => $shift->date->toDateString()
                .'#'.str_pad((string) $shift->timeSlot->position, 2, '0', STR_PAD_LEFT)
                .'#'.$shift->mission->name)
            ->groupBy(fn (Shift $shift): string => $shift->date->toDateString());
    }
}
