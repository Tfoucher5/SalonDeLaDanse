<?php

namespace App\Http\Controllers;

use App\Enums\PlanningState;
use App\Models\Edition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Écran d'accueil du bénévole : ce qu'il s'engage à faire, quand, et où en
     * est son planning.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $edition = $user->activeEdition();

        return view('dashboard', [
            'user' => $user,
            'edition' => $edition,
            'state' => PlanningState::for($user, $edition),
            'bookedCount' => $user->assignments()->count(),
            'commitmentRules' => $edition === null ? [] : $this->commitmentRules($edition),
            'contact' => array_filter(config('salon.contact')),
        ]);
    }

    /**
     * Les règles d'engagement, formulées à partir des quotas de l'édition :
     * aucune valeur n'est écrite en dur, elles restent paramétrables.
     *
     * @return array<int, string>
     */
    private function commitmentRules(Edition $edition): array
    {
        return [
            "Vous vous engagez sur {$edition->min_slots_per_volunteer} créneau minimum et {$edition->max_slots_per_volunteer} créneaux maximum sur l'ensemble du week-end.",
            'Deux missions ne peuvent pas se chevaucher : une seule mission par tranche horaire, quel que soit le jour.',
            'Trois tranches horaires consécutives le même jour sont interdites : une pause est obligatoire.',
            'Chaque créneau a un nombre de places limité. Une fois la jauge atteinte, le créneau se ferme.',
            "Votre planning reste modifiable tant que les inscriptions sont ouvertes et que l'équipe organisatrice ne l'a pas validé.",
        ];
    }
}
