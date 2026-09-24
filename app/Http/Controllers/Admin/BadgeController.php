<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DownloadBadgesRequest;
use App\Http\Requests\Admin\VolunteerSearchRequest;
use App\Models\Edition;
use App\Models\User;
use App\Services\Badges\BadgePrinter;
use App\Services\VolunteerSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les badges bénévoles prêts à imprimer : celui d'un bénévole depuis sa fiche,
 * ou toute une planche depuis la page « Badges ».
 */
class BadgeController extends Controller
{
    public function __construct(
        private readonly BadgePrinter $printer,
        private readonly VolunteerSearch $search,
    ) {}

    /**
     * La génération en masse, sur les critères de la recherche.
     *
     * La liste n'est pas paginée : « tout cocher » doit porter sur tout ce que
     * les critères retiennent, et une édition compte une centaine de bénévoles.
     */
    public function index(VolunteerSearchRequest $request): View
    {
        $edition = Edition::current();
        $criteria = $request->criteria();
        $volunteers = $edition === null ? null : $this->search->query($edition, $criteria)->get();

        return view('admin.badges.index', [
            'edition' => $edition,
            'criteria' => $criteria,
            'missions' => $edition?->missions()->get() ?? collect(),
            'days' => $edition?->days() ?? collect(),
            'volunteers' => $volunteers,
            'identifiers' => $volunteers?->mapWithKeys(fn (User $volunteer): array => [
                $volunteer->getKey() => $this->printer->identifier($volunteer, $edition),
            ]) ?? collect(),
            'withoutPhoto' => $edition === null ? collect() : $this->printer->volunteersWithoutPhoto($edition),
        ]);
    }

    /**
     * Le badge d'un seul bénévole, depuis sa fiche.
     */
    public function show(User $volunteer): Response
    {
        $edition = Edition::current();

        abort_unless(
            $edition !== null && $this->printer->isActiveVolunteer($volunteer, $edition),
            404,
            "Ce compte n'est pas un bénévole de l'édition en cours : il n'a pas de badge.",
        );

        $volunteers = collect([$volunteer]);

        return $this->printer->pdf($volunteers, $edition)
            ->download($this->printer->filename($volunteers, $edition));
    }

    /**
     * La planche groupée : un seul PDF, quatre badges par page A4.
     */
    public function download(DownloadBadgesRequest $request): Response|RedirectResponse
    {
        $edition = Edition::current();

        abort_if($edition === null, 404, "Aucune édition n'est ouverte : il n'y a aucun badge à imprimer.");

        $volunteers = $this->printer->volunteers(
            $edition,
            $request->selection(),
            $request->criteria(),
            $request->volunteerIds(),
        );

        if ($volunteers->isEmpty()) {
            return back()->withErrors(['mode' => 'Aucun bénévole ne correspond à ces critères : il n\'y a aucun badge à imprimer.']);
        }

        // Mesuré : 130 badges prennent une quinzaine de secondes, surtout pour
        // recadrer les photos. Les 30 secondes par défaut seraient trop justes
        // avec des photos de plusieurs mégaoctets.
        set_time_limit(120);

        return $this->printer->pdf($volunteers, $edition)
            ->download($this->printer->filename($volunteers, $edition));
    }
}
