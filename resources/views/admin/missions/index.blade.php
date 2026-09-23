@use('App\Enums\StaffingLevel')

<x-admin-layout title="Missions">
    <x-slot name="header">
        <x-ui.page-header
            title="Missions"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Les postes du Salon, leur jauge et leur disponibilité.' : 'Aucune édition n\'est ouverte pour le moment.'">
            @if ($edition)
                <x-slot name="actions">
                    <x-ui.button :href="route('admin.missions.create')" variant="primary" size="touch">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Nouvelle mission
                    </x-ui.button>
                </x-slot>
            @endif
        </x-ui.page-header>
    </x-slot>

    @if (session('status'))
        <x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>
    @endif

    @error('mission')
        <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
    @enderror

    @if ($edition === null)
        <x-ui.empty title="Aucune édition active">
            Les missions s'afficheront ici dès qu'une édition du Salon sera ouverte.
        </x-ui.empty>
    @elseif ($missions->isEmpty())
        <x-ui.empty title="Aucune mission pour cette édition">
            Créez la première : elle ouvrira un créneau sur chaque tranche horaire du Salon.
        </x-ui.empty>
    @else
        {{-- Une liste, pas un tableau : sur telephone chaque mission devient
             une carte a deux lignes, sans defilement lateral ; des `md`, les
             memes elements s'alignent en colonnes (`md:contents` deplie la
             ligne d'informations dans la grille). --}}
        @php $columns = 'md:grid-cols-[2rem_minmax(0,1fr)_9rem_6.5rem_9rem_1.25rem]'; @endphp

        <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-zinc-900/5">
            <div class="hidden gap-4 border-b border-zinc-200 px-4 py-3 text-xs font-bold uppercase tracking-wider text-zinc-500 md:grid {{ $columns }}" aria-hidden="true">
                <span>#</span>
                <span>Mission</span>
                <span>État</span>
                <span>Jauge</span>
                <span>Remplissage</span>
                <span></span>
            </div>

            <ul class="divide-y divide-zinc-200">
                @foreach ($missions as $mission)
                    @php
                        $capacity = (int) $mission->shifts_sum_capacity;
                        $taken = $mission->assignments_count;
                        $staffing = StaffingLevel::fromCounts($taken, $capacity);
                        $percent = $capacity > 0 ? (int) round(min($taken, $capacity) / $capacity * 100) : 0;
                    @endphp

                    {{-- Toute la ligne ouvre la mission : le lien du nom s'etire
                         sur la ligne entiere. --}}
                    <li class="relative flex flex-col gap-2 px-4 py-3 tabular-grid transition hover:bg-zinc-50 md:grid md:items-center md:gap-4 {{ $columns }}">
                        <div class="flex min-w-0 items-start gap-3 md:contents">
                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-xs font-bold text-zinc-500">{{ $mission->position }}</span>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.missions.edit', $mission) }}"
                                   class="font-semibold text-zinc-900 after:absolute after:inset-0 after:content-[''] hover:text-primary">
                                    {{ $mission->name }}
                                </a>

                                <span class="mt-0.5 block text-sm text-zinc-500">
                                    {{ $mission->is_public ? 'Ouverte aux bénévoles' : 'Attribuée par l\'équipe seulement' }}
                                    · {{ $mission->shifts_count }} créneaux
                                </span>
                            </div>

                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-zinc-400 md:hidden" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 ps-10 md:contents">
                            <span class="flex flex-wrap gap-1.5">
                                @if ($mission->is_active)
                                    <x-ui.badge tone="free" dot>Active</x-ui.badge>
                                @else
                                    <x-ui.badge tone="full" dot>Fermée</x-ui.badge>
                                @endif

                                @unless ($mission->is_public)
                                    <x-ui.badge tone="plum">Restreinte</x-ui.badge>
                                @endunless
                            </span>

                            <span class="text-sm text-zinc-900">{{ $mission->default_capacity }} / créneau</span>

                            <div class="w-32">
                                <p class="text-sm text-zinc-900"><span class="font-semibold">{{ $taken }}</span> <span class="text-zinc-500">/ {{ $capacity }} places</span></p>

                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-zinc-200" aria-hidden="true">
                                    <div class="h-full rounded-full {{ ['free' => 'bg-gauge-free', 'tight' => 'bg-gauge-tight', 'danger' => 'bg-danger/70'][$staffing->tone()] }}" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>

                            <svg class="hidden h-5 w-5 text-zinc-400 md:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <x-ui.alert>
            La jauge se règle par mission et s'applique à tous ses créneaux, tous les jours du Salon.
        </x-ui.alert>
    @endif
</x-admin-layout>
