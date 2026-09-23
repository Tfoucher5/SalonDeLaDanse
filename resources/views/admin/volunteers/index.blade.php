@use('App\Enums\PlanningState')

<x-admin-layout title="Bénévoles">
    <x-slot name="header">
        <x-ui.page-header
            title="Bénévoles"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Cherchez par nom, mission, statut de validation ou jour.' : 'Aucune édition n\'est ouverte pour le moment.'" />
    </x-slot>

    @if ($edition === null)
        <x-ui.empty title="Aucune édition active">
            La liste des bénévoles s'affichera ici dès qu'une édition du Salon sera ouverte.
        </x-ui.empty>
    @else
        <x-ui.card title="Rechercher">
            <x-admin.volunteer-filters
                :action="route('admin.volunteers.index')"
                :criteria="$criteria"
                :missions="$missions"
                :days="$days"
                target="resultats-benevoles">
                <x-ui.button :href="route('admin.exports.index', array_filter($criteria))" variant="ghost">
                    Exporter ces résultats
                </x-ui.button>
            </x-admin.volunteer-filters>
        </x-ui.card>

        {{-- Zone rejouee par le filtrage sans clic. Son identifiant est le
             contrat avec `liveFilters`. --}}
        <div id="resultats-benevoles" class="space-y-6" aria-live="polite">
        @if ($volunteers->isEmpty())
            <x-ui.empty title="Aucun bénévole ne correspond à cette recherche">
                Élargissez les critères, ou vérifiez l'orthographe du nom.
            </x-ui.empty>
        @else
            <x-ui.table>
                <x-slot name="head">
                    <th scope="col">Bénévole</th>
                    <th scope="col">Contact</th>
                    <th scope="col">Créneaux</th>
                    <th scope="col">Statut</th>
                    <th scope="col"><span class="sr-only">Fiche</span></th>
                </x-slot>

                @foreach ($volunteers as $volunteer)
                    @php $volunteerState = PlanningState::for($volunteer, $edition); @endphp

                    <tr class="tabular-grid">
                        <td>
                            <div class="flex items-center gap-3">
                                <x-ui.avatar :user="$volunteer" />

                                <span class="font-medium text-zinc-900">{{ $volunteer->full_name }}</span>
                            </div>
                        </td>

                        <td>
                            <p class="text-zinc-900">{{ $volunteer->email }}</p>
                            <p class="text-sm text-zinc-500">{{ $volunteer->phone }}</p>
                        </td>

                        <td>{{ $volunteer->assignments_count }}</td>

                        <td><x-ui.badge :tone="$volunteerState->tone()">{{ $volunteerState->label() }}</x-ui.badge></td>

                        <td class="text-right">
                            <x-ui.button :href="route('admin.volunteers.show', $volunteer)">Voir la fiche</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <x-ui.pagination :paginator="$volunteers" />
        @endif
        </div>
    @endif
</x-admin-layout>
