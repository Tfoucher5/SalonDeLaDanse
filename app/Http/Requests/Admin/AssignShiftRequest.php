<?php

namespace App\Http\Requests\Admin;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Attribution d'un créneau par l'équipe organisatrice.
 *
 * Aucune règle métier ici : elles vivent toutes dans `PlanningRules`, et
 * l'administrateur les outrepasse de toute façon. Ce request ne vérifie qu'une
 * chose — que le créneau désigné existe.
 */
class AssignShiftRequest extends FormRequest
{
    /**
     * L'autorisation est portée par la porte `admin` sur la route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shift_id.required' => 'Choisissez le créneau à attribuer.',
            'shift_id.exists' => 'Ce créneau n\'existe pas.',
        ];
    }

    public function shift(): Shift
    {
        return Shift::query()->findOrFail($this->integer('shift_id'));
    }
}
