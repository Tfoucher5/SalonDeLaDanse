<?php

namespace App\Http\Requests\Admin;

use App\Models\Edition;
use App\Models\Mission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Le socle commun à la création et à la modification d'une mission.
 *
 * La création ajoute la génération des créneaux, la modification y ajoute le
 * contrôle de jauge : les règles de forme, elles, sont les mêmes des deux côtés.
 */
abstract class MissionRequest extends FormRequest
{
    /**
     * Jauge maximale acceptée par créneau. Au-delà, c'est une saisie erronée
     * plutôt qu'une décision : le Salon ne met pas 500 personnes sur un poste.
     */
    private const MAX_CAPACITY = 200;

    /**
     * L'autorisation est portée par la porte `admin` sur la route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                // Le nom identifie la mission a l'ecran : deux « Vestiaires »
                // dans la meme edition seraient indiscernables.
                Rule::unique(Mission::class, 'name')
                    ->where('edition_id', $this->editionKey())
                    ->ignore($this->mission()?->getKey()),
            ],
            'default_capacity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_CAPACITY],
            'position' => ['required', 'integer', 'min:1', 'max:99'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Les cases à cocher absentes du corps de la requête valent « non ».
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_public' => $this->boolean('is_public'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'default_capacity' => 'nombre de personnes',
            'position' => 'ordre d\'affichage',
            'instructions' => 'consignes',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Une mission porte déjà ce nom dans cette édition.',
            'default_capacity.min' => 'Une mission accueille au moins une personne par créneau.',
            'default_capacity.max' => 'Cette jauge dépasse ce qu\'un poste peut accueillir.',
        ];
    }

    /**
     * Les attributs retenus, prêts pour `MissionCatalogue`.
     *
     * @return array{name: string, is_public: bool, is_active: bool, default_capacity: int, instructions: ?string, position: int}
     */
    public function missionAttributes(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'is_public' => $this->boolean('is_public'),
            'is_active' => $this->boolean('is_active'),
            'default_capacity' => $this->integer('default_capacity'),
            'instructions' => $this->string('instructions')->trim()->value() ?: null,
            'position' => $this->integer('position'),
        ];
    }

    /**
     * La mission visée, ou null à la création.
     */
    abstract protected function mission(): ?Mission;

    protected function editionKey(): ?int
    {
        return $this->mission()?->edition_id ?? Edition::current()?->getKey();
    }
}
