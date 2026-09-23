<?php

namespace App\Http\Requests\Admin;

use App\Models\Mission;
use App\Models\Shift;
use App\Services\MissionCatalogue;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Collection;

/**
 * Modification d'une mission existante.
 *
 * La règle qui compte est la dernière : on ne descend pas la jauge en dessous
 * de ce qui est déjà attribué. Elle est vérifiée ici, avant toute écriture, et
 * son message nomme les créneaux fautifs — « impossible » n'apprendrait rien à
 * l'administrateur, qui doit savoir où aller retirer quelqu'un.
 */
class UpdateMissionRequest extends MissionRequest
{
    /**
     * Le nombre de créneaux nommés dans le message d'erreur. Au-delà, la phrase
     * devient illisible et le compte suffit.
     */
    private const LISTED_CONFLICTS = 3;

    protected function mission(): ?Mission
    {
        return $this->route('mission');
    }

    /**
     * La jauge visée tient-elle pour tous les créneaux déjà pourvus ?
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['default_capacity'])) {
                    return;
                }

                $conflicts = app(MissionCatalogue::class)
                    ->capacityConflicts($this->route('mission'), $this->integer('default_capacity'));

                if ($conflicts->isNotEmpty()) {
                    $validator->errors()->add('default_capacity', $this->conflictMessage($conflicts));
                }
            },
        ];
    }

    /**
     * @param  Collection<int, Shift>  $conflicts
     */
    private function conflictMessage(Collection $conflicts): string
    {
        $listed = $conflicts->take(self::LISTED_CONFLICTS)
            ->map(fn (Shift $shift): string => sprintf(
                '%s %s (%d inscrits)',
                $shift->date->translatedFormat('j F'),
                $shift->timeSlot->label(),
                $shift->assignments_count,
            ))
            ->implode(', ');

        $remaining = $conflicts->count() - self::LISTED_CONFLICTS;

        return sprintf(
            'Cette jauge est trop basse pour %d créneau%s déjà pourvu%s : %s%s. '
                .'Retirez des bénévoles de ces créneaux, ou gardez une jauge au moins égale à %d.',
            $conflicts->count(),
            $conflicts->count() > 1 ? 'x' : '',
            $conflicts->count() > 1 ? 's' : '',
            $listed,
            $remaining > 0 ? sprintf(', et %d autre%s', $remaining, $remaining > 1 ? 's' : '') : '',
            (int) $conflicts->max('assignments_count'),
        );
    }
}
