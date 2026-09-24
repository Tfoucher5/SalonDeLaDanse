<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\User;
use App\Services\Badges\BadgePrinter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * La page ouverte par le QR code d'un badge, scanné avec l'appareil photo d'un
 * téléphone. Publique : n'importe qui peut vérifier qu'un badge est authentique,
 * d'où l'absence de toute coordonnée.
 *
 * Un administrateur connecté y voit en plus le contrôle de l'équipe : la
 * validation explicite et le planning de la personne, pour l'orienter à
 * l'entrée. Rien n'est enregistré : c'est un contrôle visuel.
 *
 * Le middleware `signed` a déjà écarté une adresse forgée. L'état, lui, se lit
 * maintenant : un compte retiré de l'édition n'est plus reconnu, même avec un
 * badge imprimé des semaines plus tôt.
 */
class BadgeVerificationController extends Controller
{
    public function __construct(private readonly BadgePrinter $printer) {}

    /**
     * Les identifiants arrivent bruts, sans liaison de modèle : la liaison
     * passerait avant la signature, et un compte supprimé répondrait par un
     * 404 anonyme au lieu de dire que le badge n'est plus valable.
     */
    public function __invoke(Request $request, int $edition, int $volunteer): Response
    {
        $edition = Edition::query()->find($edition);
        $volunteer = User::query()->find($volunteer);
        $viewerIsAdmin = $request->user()?->isAdmin() === true;

        // Un membre de l'équipe qui scanne sans être connecté revient ici
        // après la connexion, signature comprise.
        if ($request->user() === null) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        // Le nom reste affiché quand le badge n'est plus valable : l'agent doit
        // pouvoir constater que c'est bien celui qu'on lui présente.
        return response()->view('badges.verify', [
            'edition' => $edition,
            'volunteer' => $volunteer,
            'identifier' => $edition !== null && $volunteer !== null ? $this->printer->identifier($volunteer, $edition) : null,
            'isActive' => $edition !== null
                && $volunteer !== null
                && $this->printer->isActiveVolunteer($volunteer, $edition),
            'viewerIsAdmin' => $viewerIsAdmin,
            'shifts' => $viewerIsAdmin && $volunteer !== null ? $this->printer->assignedShifts($volunteer) : collect(),
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
