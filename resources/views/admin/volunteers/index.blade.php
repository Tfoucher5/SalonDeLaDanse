@use('App\Enums\ExportDataset')
@use('App\Enums\PlanningState')
@use('App\Enums\StaffingLevel')

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
        <x-ui.card>
            <x-admin.volunteer-filters
                :action="route('admin.volunteers.index')"
                :criteria="$criteria"
                :missions="$missions"
                :days="$days"
                target="resultats-benevoles" />
        </x-ui.card>

        {{-- Zone rejouee par le filtrage sans clic. Son identifiant est le
             contrat avec `liveFilters`. --}}
        <div id="resultats-benevoles" class="space-y-4" aria-live="polite">
        {{-- Dans la zone rejouee : l'export suit ainsi les criteres saisis,
             sans rechargement. --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="tabular-grid text-sm font-semibold text-zinc-500">
                {{ $volunteers->total() }} bénévole{{ $volunteers->total() > 1 ? 's' : '' }}
                {{ collect($criteria)->filter()->isNotEmpty() ? 'pour cette recherche' : 'inscrits' }}
            </p>

            <x-admin.export-dialog
                :datasets="[ExportDataset::Contacts, ExportDataset::Planning]"
                :criteria="$criteria"
                :missions="$missions" />
        </div>

        @if ($volunteers->isEmpty())
            <x-ui.empty title="Aucun bénévole ne correspond à cette recherche">
                Élargissez les critères, ou vérifiez l'orthographe du nom.
            </x-ui.empty>
        @else
            @php $maximum = $edition->max_slots_per_volunteer; @endphp

            {{-- Une liste, pas un tableau : sur telephone chaque bénévole
                 devient une carte a deux lignes, sans defilement lateral ; des
                 `md`, les memes elements s'alignent en colonnes (`md:contents`
                 deplie la ligne d'informations dans la grille). --}}
            @php $columns = 'md:grid-cols-[minmax(0,1fr)_8.5rem_6rem_8.5rem_1.25rem]'; @endphp

            <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-zinc-900/5">
                <div class="hidden gap-4 border-b border-zinc-200 px-4 py-3 text-xs font-bold uppercase tracking-wider text-zinc-500 md:grid {{ $columns }}" aria-hidden="true">
                    <span>Bénévole</span>
                    <span>Téléphone</span>
                    <span>Créneaux</span>
                    <span>Statut</span>
                    <span></span>
                </div>

                <ul class="divide-y divide-zinc-200">
                    @foreach ($volunteers as $volunteer)
                        @php
                            $volunteerState = PlanningState::for($volunteer, $edition);
                            $count = $volunteer->assignments_count;
                            $slotsLevel = StaffingLevel::forQuota($count, $edition->min_slots_per_volunteer, $maximum);
                        @endphp

                        {{-- Toute la ligne ouvre la fiche : le lien du nom s'etire
                             sur la ligne entiere. --}}
                        <li class="relative flex flex-col gap-2 px-4 py-3 tabular-grid transition hover:bg-zinc-50 md:grid md:items-center md:gap-4 {{ $columns }}">
                            <div class="flex min-w-0 items-center gap-3">
                                <x-ui.avatar :user="$volunteer" size="h-10 w-10" />

                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.volunteers.show', $volunteer) }}"
                                       class="block truncate font-semibold text-zinc-900 after:absolute after:inset-0 after:content-[''] hover:text-primary">
                                        {{ $volunteer->full_name }}
                                    </a>
                                    <p class="truncate text-sm text-zinc-500">{{ $volunteer->email }}</p>
                                </div>

                                <svg class="h-5 w-5 shrink-0 text-zinc-400 md:hidden" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                                </svg>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 ps-[3.25rem] md:contents">
                                <span class="hidden truncate text-sm text-zinc-500 md:block">{{ $volunteer->phone }}</span>

                                <div class="w-20">
                                    <p class="text-sm font-semibold {{ $slotsLevel->textClass() }}">{{ $count }} / {{ $maximum }}<span class="sr-only"> créneaux</span></p>

                                    <div class="mt-1 flex gap-1" aria-hidden="true">
                                        @for ($segment = 1; $segment <= $maximum; $segment++)
                                            <span class="h-1.5 flex-1 rounded-full {{ $segment <= $count ? $slotsLevel->barClass() : 'bg-zinc-200' }}"></span>
                                        @endfor
                                    </div>
                                </div>

                                <div><x-ui.badge :tone="$volunteerState->adminTone()" dot>{{ $volunteerState->adminLabel() }}</x-ui.badge></div>

                                <svg class="hidden h-5 w-5 text-zinc-400 md:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <x-ui.pagination :paginator="$volunteers" />
        @endif
        </div>
    @endif
</x-admin-layout>
