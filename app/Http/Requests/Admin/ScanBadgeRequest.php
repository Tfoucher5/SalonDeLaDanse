<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Un code lu par le scanner du back-office, ou saisi à la main.
 */
class ScanBadgeRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.*' => 'Scannez un badge ou saisissez son identifiant.',
        ];
    }

    public function code(): string
    {
        return $this->string('code')->value();
    }
}
