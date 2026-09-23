<?php

namespace App\Http\Requests\Admin;

use App\Models\Edition;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Les critères de recherche du back-office.
 *
 * La recherche ne modifie rien, mais ses critères restent validés ici : une
 * mission inconnue ou un jour hors édition doit être écarté avant la requête,
 * pas filtré dans le contrôleur.
 */
class VolunteerSearchRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:100'],
            'mission' => ['nullable', 'integer', 'exists:missions,id'],
            'status' => ['nullable', 'in:validated,pending'],
            'day' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mission.exists' => 'Cette mission n\'existe pas.',
            'status.in' => 'Ce statut de validation n\'existe pas.',
            'day.date_format' => 'Ce jour n\'est pas une date valide.',
        ];
    }

    /**
     * Les critères retenus, nettoyés de leurs valeurs vides.
     *
     * Un champ laissé vide dans le formulaire arrive en chaîne vide : sans ce
     * nettoyage, il filtrerait sur rien et viderait la liste.
     *
     * @return array{name: ?string, mission: ?int, status: ?string, day: ?string}
     */
    public function criteria(): array
    {
        $day = $this->string('day')->trim()->value();

        return [
            'name' => $this->string('name')->trim()->value() ?: null,
            'mission' => $this->integer('mission') ?: null,
            'status' => $this->string('status')->trim()->value() ?: null,
            'day' => $this->dayIsInEdition($day) ? $day : null,
        ];
    }

    /**
     * Le jour demandé fait-il partie de l'édition courante ?
     */
    private function dayIsInEdition(string $day): bool
    {
        if ($day === '') {
            return false;
        }

        $edition = Edition::current();

        return $edition !== null
            && $edition->days()->contains(fn ($date): bool => $date->toDateString() === $day);
    }
}
