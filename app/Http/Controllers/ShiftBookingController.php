<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingRuleException;
use App\Http\Requests\Planning\BookShiftRequest;
use App\Http\Requests\Planning\ReleaseShiftRequest;
use App\Models\Shift;
use App\Services\PlanningRules;
use Illuminate\Http\RedirectResponse;

/**
 * Composition du planning : ajout et retrait d'un créneau.
 *
 * Le contrôleur ne connaît aucune règle. Il relaie le refus de PlanningRules
 * dans le même sac d'erreurs que le FormRequest, pour que le bénévole lise le
 * même message quel que soit l'endroit où la règle a mordu.
 *
 * `focus_shift` désigne le créneau concerné : la grille rouvre sa tranche
 * horaire et revient dessus, au lieu de remonter en haut de page.
 */
class ShiftBookingController extends Controller
{
    public function __construct(private readonly PlanningRules $rules) {}

    public function store(BookShiftRequest $request, Shift $shift): RedirectResponse
    {
        try {
            $this->rules->book($request->user(), $shift);
        } catch (BookingRuleException $exception) {
            return back()
                ->withErrors(['shift' => $exception->getMessage()])
                ->with('focus_shift', $shift->id);
        }

        return back()
            ->with('status', 'Créneau ajouté à votre planning.')
            ->with('focus_shift', $shift->id);
    }

    public function destroy(ReleaseShiftRequest $request, Shift $shift): RedirectResponse
    {
        try {
            $this->rules->release($request->user(), $shift);
        } catch (BookingRuleException $exception) {
            return back()
                ->withErrors(['shift' => $exception->getMessage()])
                ->with('focus_shift', $shift->id);
        }

        return back()
            ->with('status', 'Créneau retiré de votre planning.')
            ->with('focus_shift', $shift->id);
    }
}
