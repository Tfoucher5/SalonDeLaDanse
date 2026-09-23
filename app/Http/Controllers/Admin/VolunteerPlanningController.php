<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingRule;
use App\Exceptions\BookingRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignShiftRequest;
use App\Models\Edition;
use App\Models\Shift;
use App\Models\User;
use App\Services\PlanningRules;
use Illuminate\Http\RedirectResponse;

/**
 * La main de l'administrateur sur le planning d'un bénévole.
 *
 * Attribuer, retirer, rouvrir : ces trois écritures passent outre le
 * verrouillage et les règles de composition. Le contrôleur ne les connaît pas
 * pour autant — `PlanningRules` reste le seul endroit où elles sont écrites,
 * et le seul à décider de ce qui reste impossible.
 */
class VolunteerPlanningController extends Controller
{
    public function __construct(private readonly PlanningRules $rules) {}

    /**
     * Force l'attribution d'un créneau, mission restreinte comprise.
     */
    public function store(AssignShiftRequest $request, User $volunteer): RedirectResponse
    {
        $shift = $request->shift();

        // Lue avant l'écriture : une fois le créneau attribué, la règle
        // « déjà réservé » masquerait celle qu'on vient réellement d'outrepasser.
        $overridden = $this->rules->overriddenRuleFor($volunteer, $shift);

        try {
            $this->rules->assignAsAdmin($volunteer, $shift);
        } catch (BookingRuleException $exception) {
            return back()->withErrors(['shift_id' => $exception->getMessage()]);
        }

        return back()->with('status', $this->assignmentMessage(
            $shift,
            $overridden?->overrideNotice($volunteer->activeEdition()),
        ));
    }

    /**
     * Retire un créneau, planning validé ou non.
     */
    public function destroy(User $volunteer, Shift $shift): RedirectResponse
    {
        try {
            $this->rules->revokeAsAdmin($volunteer, $shift);
        } catch (BookingRuleException $exception) {
            return back()->withErrors(['shift_id' => $exception->getMessage()]);
        }

        return back()->with('status', 'Créneau retiré du planning.');
    }

    /**
     * Fige définitivement le planning du bénévole.
     *
     * La validation appartient à l'organisation, pas au bénévole : c'est le
     * seul endroit de l'application d'où elle part.
     */
    public function validate(User $volunteer): RedirectResponse
    {
        try {
            $this->rules->validateAsAdmin($volunteer);
        } catch (BookingRuleException $exception) {
            return back()->withErrors([
                'planning' => $this->validationRefusal($exception->rule, $volunteer->activeEdition()),
            ]);
        }

        return back()->with('status', 'Planning validé définitivement. Il passe en lecture seule pour le bénévole.');
    }

    /**
     * Lève la validation définitive et rend la main au bénévole.
     */
    public function unlock(User $volunteer): RedirectResponse
    {
        $this->rules->unlock($volunteer);

        if ($this->rules->isLockedByForcedAssignment($volunteer)) {
            return back()->with(
                'status',
                'Validation levée. Le planning reste verrouillé : une attribution de '
                .'l\'équipe organisatrice y figure. Retirez-la pour rendre la main au bénévole.'
            );
        }

        return back()->with('status', 'Planning rouvert : le bénévole peut de nouveau le modifier.');
    }

    /**
     * Le refus de validation, dit à l'administrateur.
     *
     * Le message du quota minimum est déjà tourné pour le back-office ; les
     * deux autres cas s'adressent au bénévole et seraient à contresens ici.
     */
    private function validationRefusal(BookingRule $rule, ?Edition $edition): string
    {
        return match ($rule) {
            BookingRule::PlanningValidated => 'Ce planning est déjà validé définitivement.',
            BookingRule::PlanningClosed => "Aucune édition n'est ouverte : ce planning ne peut pas être validé.",
            default => $rule->message($edition),
        };
    }

    /**
     * Le compte rendu de l'attribution, règle outrepassée comprise.
     *
     * Forcer sans le dire serait le plus sûr moyen de sur-remplir un créneau
     * sans s'en apercevoir : l'écran nomme toujours la règle franchie.
     */
    private function assignmentMessage(Shift $shift, ?string $overridden): string
    {
        $message = 'Créneau attribué : '.$shift->mission->name.', '.$shift->timeSlot->label()
            .' le '.$shift->date->translatedFormat('j F').'.';

        return $overridden === null
            ? $message
            : $message.' Cette attribution outrepasse une règle : '.$overridden;
    }
}
