<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Modification par l'administrateur des informations verrouillées d'un
 * bénévole.
 *
 * Les règles de forme sont exactement celles de l'inscription : outrepasser le
 * verrou du profil ne veut pas dire accepter un e-mail invalide ou un doublon.
 */
class UpdateVolunteerRequest extends FormRequest
{
    /**
     * La politique décide, pas le rôle recopié ici : c'est elle qui sait que
     * le profil verrouillé reste ouvert à l'administrateur.
     */
    public function authorize(): bool
    {
        return $this->user()->can('updatePersonalInformation', $this->volunteer());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->volunteer()->getKey()),
            ],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9 .+()-]{6,30}$/'],
            'birth_date' => ['required', 'date', 'after:1900-01-01', 'before:today'],
            // La photo reste facultative ici : une modification de nom ne doit
            // pas obliger l'administrateur à en redemander une au bénévole.
            'photo' => [
                'nullable',
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
            'photo' => 'photo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Ce numéro de téléphone n\'est pas valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'photo.max' => 'La photo ne doit pas dépasser :max kilo-octets.',
        ];
    }

    /**
     * Les informations retenues, photo mise à part.
     *
     * @return array{first_name: string, last_name: string, email: string, phone: string, birth_date: string}
     */
    public function personalInformation(): array
    {
        return collect($this->validated())->except('photo')->all();
    }

    public function photo(): ?UploadedFile
    {
        return $this->file('photo');
    }

    private function volunteer(): User
    {
        return $this->route('volunteer');
    }
}
