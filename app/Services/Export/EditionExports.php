<?php

namespace App\Services\Export;

use App\Enums\ExportDataset;
use App\Enums\ExportFormat;
use App\Enums\PlanningState;
use App\Models\Edition;
use App\Models\Shift;
use App\Models\User;
use App\Services\EditionPlanning;
use App\Services\VolunteerSearch;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Les trois feuilles du back-office, construites à partir de l'édition.
 *
 * Aucune mise en forme ici : cette classe ne produit que des en-têtes et des
 * lignes, que le CSV et le classeur Excel habillent ensuite à l'identique.
 *
 * Les missions sous restriction sont comprises partout : un export sert à
 * l'organisation, la confidentialité protège les bénévoles entre eux, pas de
 * l'équipe.
 */
class EditionExports
{
    public function __construct(
        private readonly VolunteerSearch $search,
        private readonly EditionPlanning $planning,
    ) {}

    /**
     * @param  array{name: ?string, mission: ?int, status: ?string, day: ?string}  $criteria
     */
    public function build(Edition $edition, ExportDataset $dataset, array $criteria): Spreadsheet
    {
        $applicable = $this->applicableCriteria($dataset, $criteria);

        return match ($dataset) {
            ExportDataset::Planning => $this->planningSheet($edition, $applicable),
            ExportDataset::Missions => $this->missionsSheet($edition, $applicable),
            ExportDataset::Contacts => $this->contactsSheet($edition, $applicable),
        };
    }

    /**
     * Le nom du fichier téléchargé : ce qu'il contient, pour quelle édition,
     * et quand il a été sorti. Deux exports du même jour se distinguent une
     * fois ouverts, pas dans le dossier de téléchargement.
     */
    public function filename(Edition $edition, ExportDataset $dataset, ExportFormat $format): string
    {
        return implode('-', [
            $dataset->slug(),
            Str::slug($edition->name),
            now()->format('Ymd-Hi'),
        ]).'.'.$format->extension();
    }

    /**
     * Une ligne par créneau attribué, dans l'ordre chronologique.
     */
    private function planningSheet(Edition $edition, array $criteria): Spreadsheet
    {
        $rows = $this->volunteersWithShifts($edition, $criteria)
            ->flatMap(fn (User $volunteer): Collection => $volunteer->shifts->map(
                fn (Shift $shift): array => ['volunteer' => $volunteer, 'shift' => $shift],
            ))
            ->sortBy(fn (array $entry): string => $this->chronologicalKey($entry['shift'])
                .'#'.$entry['shift']->mission->name
                .'#'.$entry['volunteer']->last_name
                .'#'.$entry['volunteer']->first_name)
            ->map(fn (array $entry): array => [
                ...$this->slotColumns($entry['shift']),
                $entry['shift']->mission->name,
                $this->access($entry['shift']),
                $entry['volunteer']->last_name,
                $entry['volunteer']->first_name,
                $entry['volunteer']->email,
                $entry['volunteer']->phone,
                $entry['shift']->pivot->assigned_by_admin ? 'Équipe organisatrice' : 'Bénévole',
                PlanningState::for($entry['volunteer'], $edition)->adminLabel(),
            ])
            ->values()
            ->all();

        return new Spreadsheet(
            ExportDataset::Planning->sheetTitle(),
            [
                'Date', 'Jour', 'Début', 'Fin', 'Mission', 'Accès',
                'Nom', 'Prénom', 'E-mail', 'Téléphone', 'Attribution', 'Statut du planning',
            ],
            $rows,
        );
    }

    /**
     * Une ligne par créneau ouvert, mission par mission.
     *
     * Les créneaux sans personne y figurent : ce sont eux qu'on cherche en
     * ouvrant cette feuille.
     */
    private function missionsSheet(Edition $edition, array $criteria): Spreadsheet
    {
        $rows = $this->planning
            ->shifts($edition, $criteria['mission'], $criteria['day'])
            ->sortBy(fn (Shift $shift): string => $shift->mission->name.'#'.$this->chronologicalKey($shift))
            ->map(function (Shift $shift): array {
                $taken = $shift->volunteers->count();

                return [
                    $shift->mission->name,
                    $this->access($shift),
                    ...$this->slotColumns($shift),
                    $shift->capacity,
                    $taken,
                    max(0, $shift->capacity - $taken),
                    $shift->volunteers
                        ->map(fn (User $volunteer): string => $volunteer->full_name)
                        ->sort()
                        ->implode(' · '),
                ];
            })
            ->values()
            ->all();

        return new Spreadsheet(
            ExportDataset::Missions->sheetTitle(),
            [
                'Mission', 'Accès', 'Date', 'Jour', 'Début', 'Fin',
                'Places', 'Prises', 'Restantes', 'Bénévoles',
            ],
            $rows,
        );
    }

    /**
     * Une ligne par bénévole, pour joindre quelqu'un sans ouvrir l'application.
     */
    private function contactsSheet(Edition $edition, array $criteria): Spreadsheet
    {
        $rows = $this->search->query($edition, $criteria)
            ->get()
            ->map(fn (User $volunteer): array => [
                $volunteer->last_name,
                $volunteer->first_name,
                $volunteer->email,
                $volunteer->phone,
                $volunteer->birth_date?->format('d/m/Y') ?? '',
                (int) $volunteer->assignments_count,
                PlanningState::for($volunteer, $edition)->adminLabel(),
                $volunteer->planning_validated_at?->format('d/m/Y H:i') ?? '',
                $volunteer->created_at?->format('d/m/Y H:i') ?? '',
            ])
            ->values()
            ->all();

        return new Spreadsheet(
            ExportDataset::Contacts->sheetTitle(),
            [
                'Nom', 'Prénom', 'E-mail', 'Téléphone', 'Date de naissance',
                'Créneaux', 'Statut du planning', 'Validé le', 'Compte créé le',
            ],
            $rows,
        );
    }

    /**
     * Les bénévoles retenus, planning chargé d'un coup.
     *
     * @return Collection<int, User>
     */
    private function volunteersWithShifts(Edition $edition, array $criteria): Collection
    {
        return $this->search->query($edition, $criteria)
            ->with([
                'shifts.mission:id,name,is_public,is_active,position',
                'shifts.timeSlot:id,starts_at,ends_at,position',
            ])
            ->get();
    }

    /**
     * Les critères qui ont un sens pour cette feuille ; les autres sont
     * neutralisés plutôt qu'appliqués de travers.
     *
     * @param  array{name: ?string, mission: ?int, status: ?string, day: ?string}  $criteria
     * @return array{name: ?string, mission: ?int, status: ?string, day: ?string}
     */
    private function applicableCriteria(ExportDataset $dataset, array $criteria): array
    {
        $applicable = $dataset->filters();

        return collect($criteria)
            ->map(fn (string|int|null $value, string $key) => in_array($key, $applicable, strict: true) ? $value : null)
            ->all();
    }

    /**
     * Les quatre colonnes de temps, identiques d'une feuille à l'autre.
     *
     * @return array<int, string>
     */
    private function slotColumns(Shift $shift): array
    {
        return [
            $shift->date->toDateString(),
            ucfirst($shift->date->translatedFormat('l')),
            substr((string) $shift->timeSlot->starts_at, 0, 5),
            substr((string) $shift->timeSlot->ends_at, 0, 5),
        ];
    }

    /**
     * Clé de tri d'un créneau dans le temps : le jour, puis le rang de la
     * tranche — jamais l'heure sous forme de texte, qui trierait mal.
     */
    private function chronologicalKey(Shift $shift): string
    {
        return $shift->date->toDateString().'#'.str_pad(
            (string) $shift->timeSlot->position, 2, '0', STR_PAD_LEFT
        );
    }

    private function access(Shift $shift): string
    {
        return $shift->mission->is_public ? 'Ouverte' : 'Restreinte';
    }
}
