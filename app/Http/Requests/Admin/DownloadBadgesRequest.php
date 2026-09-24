<?php

namespace App\Http\Requests\Admin;

use App\Enums\BadgeSelection;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Une génération groupée de badges.
 *
 * Les critères sont ceux de la recherche, hérités tels quels : en mode
 * « filtres », le serveur recalcule la liste depuis eux. En mode « sélection »,
 * chaque identifiant reçu doit désigner un bénévole de l'édition courante : un
 * compte administrateur ou d'une autre édition est refusé, pas ignoré.
 */
class DownloadBadgesRequest extends VolunteerSearchRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'mode' => ['required', Rule::enum(BadgeSelection::class)],
            'volunteers' => ['exclude_unless:mode,'.BadgeSelection::Selection->value, 'required', 'array', 'max:500'],
            'volunteers.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'mode.*' => 'Choisissez les bénévoles à imprimer.',
            'volunteers.required' => 'Cochez au moins un bénévole.',
            'volunteers.*' => 'Cette sélection de bénévoles n\'est pas valide.',
        ];
    }

    /**
     * La vérification qui demande la base : des bénévoles de l'édition, tous.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $edition = Edition::current();

                if ($edition === null) {
                    return;
                }

                if ($this->selection() === BadgeSelection::Selection) {
                    $known = User::query()->volunteers()->ofEdition($edition)->whereKey($this->volunteerIds())->count();

                    if ($known !== count($this->volunteerIds())) {
                        $validator->errors()->add('volunteers', 'Un des bénévoles cochés n\'appartient pas à cette édition.');
                    }
                }
            },
        ];
    }

    public function selection(): BadgeSelection
    {
        return $this->enum('mode', BadgeSelection::class);
    }

    /**
     * Les identifiants cochés, sans doublon.
     *
     * Une case visible et la mémoire de la sélection peuvent envoyer le même
     * bénévole deux fois : il n'aura qu'un badge.
     *
     * @return array<int, int>
     */
    public function volunteerIds(): array
    {
        return collect($this->input('volunteers', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
