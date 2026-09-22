<?php

namespace App\Http\Requests\Auth;

use App\Services\VolunteerRegistrar;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class InvitationCodeRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
        ];
    }

    /**
     * Les codes sont envoyes en majuscules : on ne penalise pas une saisie
     * en minuscules ni une espace collee au copier-coller.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => mb_strtoupper(trim((string) $this->input('code')))]);
        }
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (! app(VolunteerRegistrar::class)->codeIsAvailable($this->validatedCode())) {
                    $validator->errors()->add(
                        'code',
                        "Ce code d'invitation n'est pas valide ou a déjà été utilisé."
                    );
                }
            },
        ];
    }

    public function validatedCode(): string
    {
        return (string) $this->input('code');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['code' => "code d'invitation"];
    }
}
