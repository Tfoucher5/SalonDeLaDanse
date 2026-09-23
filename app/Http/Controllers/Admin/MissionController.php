<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MissionInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMissionRequest;
use App\Http\Requests\Admin\UpdateMissionRequest;
use App\Models\Edition;
use App\Models\Mission;
use App\Services\MissionCatalogue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Le catalogue des missions : en créer, en fermer, régler leur jauge.
 *
 * Toutes les décisions qui touchent aux inscriptions existantes sont prises
 * par `MissionCatalogue` ou par le `FormRequest` ; le contrôleur ne fait que
 * relayer leur refus.
 */
class MissionController extends Controller
{
    public function __construct(private readonly MissionCatalogue $catalogue) {}

    public function index(): View
    {
        $edition = Edition::current();

        return view('admin.missions.index', [
            'edition' => $edition,
            'missions' => $edition === null
                ? collect()
                : $edition->missions()->withCount(['shifts', 'assignments'])->withSum('shifts', 'capacity')->get(),
        ]);
    }

    public function create(): View
    {
        $edition = Edition::current();

        abort_if($edition === null, 404, "Aucune édition n'est ouverte : il n'y a rien à organiser.");

        return view('admin.missions.create', [
            'edition' => $edition,
            // Une mission neuve se range apres les autres, sans les bousculer.
            'nextPosition' => (int) $edition->missions()->max('position') + 1,
        ]);
    }

    public function store(StoreMissionRequest $request): RedirectResponse
    {
        $edition = Edition::current();

        abort_if($edition === null, 404, "Aucune édition n'est ouverte : il n'y a rien à organiser.");

        $mission = $this->catalogue->create($edition, $request->missionAttributes());

        return to_route('admin.missions.index')->with('status', sprintf(
            '« %s » est créée, avec un créneau sur chaque tranche horaire des %d jours du Salon.',
            $mission->name,
            $edition->days()->count(),
        ));
    }

    public function edit(Mission $mission): View
    {
        return view('admin.missions.edit', [
            'mission' => $mission,
            'edition' => $mission->edition,
            'assigned' => $this->catalogue->assignedCount($mission),
        ]);
    }

    public function update(UpdateMissionRequest $request, Mission $mission): RedirectResponse
    {
        $wasActive = $mission->is_active;
        $attributes = $request->missionAttributes();

        $this->catalogue->update($mission, $attributes);

        return to_route('admin.missions.index')
            ->with('status', $this->updateMessage($mission, $wasActive));
    }

    public function destroy(Mission $mission): RedirectResponse
    {
        try {
            $this->catalogue->delete($mission);
        } catch (MissionInUseException $exception) {
            return back()->withErrors(['mission' => $exception->getMessage()]);
        }

        return to_route('admin.missions.index')
            ->with('status', sprintf('« %s » et ses créneaux ont été supprimés.', $mission->name));
    }

    /**
     * Le compte rendu de la modification.
     *
     * Fermer une mission qui compte des inscrits n'est pas une erreur — les
     * bénévoles gardent leur poste — mais cela ne doit pas passer inaperçu.
     */
    private function updateMessage(Mission $mission, bool $wasActive): string
    {
        $message = sprintf('« %s » est à jour. Sa jauge est appliquée à tous ses créneaux.', $mission->name);

        if (! $wasActive || $mission->is_active) {
            return $message;
        }

        $assigned = $this->catalogue->assignedCount($mission);

        if ($assigned === 0) {
            return $message.' Elle ne figure plus dans la grille des bénévoles.';
        }

        return $message.sprintf(
            ' Elle ne figure plus dans la grille, mais %d créneau%s y %s toujours attribué%s : '
                .'ces bénévoles gardent leur poste.',
            $assigned,
            $assigned > 1 ? 'x' : '',
            $assigned > 1 ? 'sont' : 'est',
            $assigned > 1 ? 's' : '',
        );
    }
}
