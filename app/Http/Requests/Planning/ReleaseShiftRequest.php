<?php

namespace App\Http\Requests\Planning;

use App\Enums\BookingRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Retrait d'un créneau du planning en brouillon.
 *
 * On ne retire que ce qu'on a retenu : viser le créneau d'un autre bénévole se
 * solde par un message, pas par une suppression silencieuse.
 */
class ReleaseShiftRequest extends FormRequest
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
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $holdsShift = $this->user()
                    ->assignments()
                    ->where('shift_id', $this->route('shift')->getKey())
                    ->exists();

                if (! $holdsShift) {
                    $validator->errors()->add('shift', BookingRule::NotBooked->message());
                }
            },
        ];
    }
}
