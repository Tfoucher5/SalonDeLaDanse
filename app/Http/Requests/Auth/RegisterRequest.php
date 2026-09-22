<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9 .+()-]{6,30}$/'],
            'birth_date' => ['required', 'date', 'after:1900-01-01', 'before:today'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'photo' => [
                'required',
                'image',
                'mimes:'.implode(',', config('salon.photo.mimes')),
                'max:'.config('salon.photo.max_kilobytes'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'adresse e-mail',
            'phone' => 'téléphone',
            'birth_date' => 'date de naissance',
            'password' => 'mot de passe',
            'photo' => 'photo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Le téléphone ne doit contenir que des chiffres, des espaces et les signes + . ( ) -.',
            'birth_date.before' => "La date de naissance doit être antérieure à aujourd'hui.",
            'photo.required' => 'Une photo récente est obligatoire.',
            'photo.image' => 'La photo doit être une image.',
            'photo.mimes' => 'La photo doit être au format JPEG, PNG ou WebP.',
            'photo.max' => 'La photo ne doit pas dépasser :max kilo-octets.',
        ];
    }
}
