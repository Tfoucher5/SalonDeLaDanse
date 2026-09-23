<?php

namespace App\Services;

use App\Exceptions\MissionInUseException;
use App\Models\Edition;
use App\Models\Mission;
use App\Models\Shift;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Le catalogue des missions, tel que l'administrateur le gère.
 *
 * La jauge reste portée par les créneaux — c'est le modèle de données, et elle
 * reste réglable créneau par créneau. `missions.default_capacity` en est la
 * valeur de référence : la modifier réécrit tous les créneaux de la mission.
 *
 * Deux garde-fous vivent ici, parce qu'ils touchent aux inscriptions existantes
 * et ne doivent pas dépendre de l'écran qui appelle :
 *
 * - on ne descend pas une jauge en dessous de ce qui est déjà attribué ;
 * - on ne supprime pas une mission sur laquelle des bénévoles sont inscrits.
 */
class MissionCatalogue
{
    /**
     * Crée la mission et le créneau correspondant à chaque jour × tranche.
     *
     * Une mission sans créneau ne serait réservable nulle part : la grille est
     * le produit cartésien missions × jours × tranches, on le complète.
     *
     * @param  array{name: string, is_public: bool, is_active: bool, default_capacity: int, instructions: ?string, position: int}  $attributes
     */
    public function create(Edition $edition, array $attributes): Mission
    {
        return DB::transaction(function () use ($edition, $attributes): Mission {
            $mission = $edition->missions()->create([
                ...$attributes,
                // Le slug est fige a la creation : les seeders s'en servent de
                // cle naturelle, le renommer ferait naitre un doublon.
                'slug' => $this->availableSlug($edition, $attributes['name']),
            ]);

            $this->openShifts($edition, $mission);

            return $mission;
        });
    }

    /**
     * Met à jour la mission, et propage sa jauge à tous ses créneaux.
     *
     * L'appelant a vérifié `capacityConflicts()` en amont ; la transaction
     * garantit qu'une jauge à moitié appliquée n'existe pas.
     *
     * @param  array{name: string, is_public: bool, is_active: bool, default_capacity: int, instructions: ?string, position: int}  $attributes
     */
    public function update(Mission $mission, array $attributes): void
    {
        DB::transaction(function () use ($mission, $attributes): void {
            $mission->fill($attributes)->save();

            $mission->shifts()->update(['capacity' => $attributes['default_capacity']]);
        });
    }

    /**
     * Les créneaux qui comptent déjà plus d'inscrits que la jauge visée.
     *
     * C'est le refus le plus utile du back-office : baisser une jauge sous le
     * nombre de personnes déjà en place mettrait la base dans un état que
     * l'application refuse de produire par ailleurs.
     *
     * @return Collection<int, Shift>
     */
    public function capacityConflicts(Mission $mission, int $capacity): Collection
    {
        // `has()` compare une sous-requete dans le `where` ; un `having` sur
        // l'alias de `withCount` exigerait un `group by` que SQLite refuse.
        return $mission->shifts()
            ->with(['timeSlot:id,starts_at,ends_at,position'])
            ->withCount('assignments')
            ->has('assignments', '>', $capacity)
            ->get()
            ->sortBy(fn (Shift $shift): string => $shift->date->toDateString()
                .'#'.str_pad((string) $shift->timeSlot->position, 2, '0', STR_PAD_LEFT))
            ->values();
    }

    /**
     * Supprime la mission et ses créneaux.
     *
     * @throws MissionInUseException si des bénévoles y sont inscrits
     */
    public function delete(Mission $mission): void
    {
        $assigned = $mission->assignments()->count();

        if ($assigned > 0) {
            throw MissionInUseException::make($mission, $assigned);
        }

        // Les creneaux tombent avec la mission : la contrainte est en cascade.
        $mission->delete();
    }

    /**
     * Le nombre de bénévoles inscrits sur les créneaux de cette mission.
     *
     * Ce n'est pas un nombre de personnes distinctes : un bénévole peut tenir
     * deux créneaux de la même mission, et cela fait bien deux postes à pourvoir.
     */
    public function assignedCount(Mission $mission): int
    {
        return $mission->assignments()->count();
    }

    /**
     * Ouvre un créneau par jour et par tranche horaire.
     */
    private function openShifts(Edition $edition, Mission $mission): void
    {
        $timeSlots = $edition->timeSlots()->get();

        foreach ($edition->days() as $day) {
            foreach ($timeSlots as $timeSlot) {
                $edition->shifts()->updateOrCreate(
                    [
                        'mission_id' => $mission->getKey(),
                        'time_slot_id' => $timeSlot->getKey(),
                        'date' => $day->toDateString(),
                    ],
                    ['capacity' => $mission->default_capacity],
                );
            }
        }
    }

    /**
     * Un slug libre au sein de l'édition — la contrainte d'unicité porte sur
     * le couple `(edition_id, slug)`.
     */
    private function availableSlug(Edition $edition, string $name): string
    {
        $base = Str::slug($name) ?: 'mission';
        $slug = $base;
        $suffix = 2;

        while ($edition->missions()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
