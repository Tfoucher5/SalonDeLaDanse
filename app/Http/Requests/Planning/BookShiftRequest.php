<?php

namespace App\Http\Requests\Planning;

use App\Services\PlanningRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Ajout d'un créneau au planning en brouillon.
 *
 * Aucun champ à valider : c'est le créneau visé, résolu par la route, qui est
 * confronté aux règles métier. Le message rendu est celui de la règle violée.
 * PlanningRules revérifie tout sous verrou au moment d'écrire ; cette passe-ci
 * ne sert qu'à répondre proprement, avant d'ouvrir une transaction.
 */
class BookShiftRequest extends FormRequest
{
    use FocusesShiftOnFailure;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<int, callable>
     */
    public function after(PlanningRules $rules): array
    {
        return [
            function (Validator $validator) use ($rules): void {
                $user = $this->user();
                $violation = $rules->violationFor($user, $this->route('shift'));

                if ($violation !== null) {
                    $validator->errors()->add('shift', $violation->message($user->activeEdition()));
                }
            },
        ];
    }
}
