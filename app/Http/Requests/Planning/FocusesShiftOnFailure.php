<?php

namespace App\Http\Requests\Planning;

use Illuminate\Contracts\Validation\Validator;

/**
 * Un refus renvoie la grille sur le créneau visé plutôt qu'en haut de page :
 * le bénévole lit le motif là où il a cliqué.
 */
trait FocusesShiftOnFailure
{
    protected function failedValidation(Validator $validator): void
    {
        $this->session()->flash('focus_shift', $this->route('shift')->id);

        parent::failedValidation($validator);
    }
}
