@use('App\Enums\ExportDataset')
@use('App\Enums\StaffingLevel')

<x-admin-layout title="Planning">
    <x-slot name="header">
        <x-ui.page-header
            title="Planning du Salon"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Jour par jour, qui est inscrit sur quel créneau.' : 'Aucune édition n\'est ouverte pour le moment.'" />
    </x-slot>

    @if ($edition === null)
        <x-ui.empty title="Aucune édition active">
            Le planning s'affichera ici dès qu'une édition du Salon sera ouverte.
        </x-ui.empty>
    @else
        {{-- Barre de filtres d'un seul tenant (maquette « Planning Admin,
             en-tete et filtre integres ») : les jours a gauche, mission,
             recherche et export a droite, qui s'etirent pour occuper la ligne.
             Filtrage sans clic ; sans JavaScript, le bouton « Filtrer » reste.

             Les onglets de jour et l'export portent les criteres courants :
             ils sont rejoues avec les resultats (`liveFilters`).

             Sous `lg`, seuls les jours suivent le defilement. Un element
             collant ne sort pas de son parent : la carte s'efface donc
             (`contents`) pour que les jours collent a la page entiere. --}}
        <form method="GET"
              action="{{ route('admin.planning') }}"
              x-data="liveFilters('resultats-planning jours-planning export-planning')"
              :aria-busy="busy"
              class="max-lg:contents lg:sticky lg:top-20 lg:z-30 lg:flex lg:items-center lg:gap-2 lg:rounded-2xl lg:bg-white lg:p-2 lg:shadow-card lg:ring-1 lg:ring-zinc-900/5">
            <input type="hidden" name="day" value="{{ $selectedDay?->toDateString() }}">

            <div class="sticky top-16 z-30 -mx-4 bg-zinc-50/90 px-4 py-2 backdrop-blur-md sm:mx-0 sm:px-0 lg:static lg:m-0 lg:w-[26rem] lg:shrink-0 lg:bg-transparent lg:p-0 lg:backdrop-blur-none">
                <nav id="jours-planning" x-on:click="follow($event, 'day')" class="flex gap-1 rounded-xl bg-zinc-100 p-1" aria-label="Jours du Salon">
                    @foreach ($days as $index => $day)
                        <x-planning.day-tab
                            :day="$day"
                            :index="$index"
                            :selected="$selectedDay?->isSameDay($day) ?? false"
                            route="admin.planning"
                            :params="array_filter(['mission' => $criteria['mission'], 'name' => $criteria['name']])" />
                    @endforeach
                </nav>
            </div>

            <div class="mt-2 flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center lg:mt-0">
                <label for="filtre-mission" class="sr-only">Mission</label>

                <x-ui.select x-on:change="refresh()" id="filtre-mission" name="mission" class="sm:flex-1">
                    <option value="">Toutes les missions ({{ $missions->count() }})</option>

                    @foreach ($missions as $mission)
                        <option value="{{ $mission->id }}" @selected($criteria['mission'] === $mission->id)>
                            {{ $mission->name }}{{ $mission->is_public ? '' : ' (restreinte)' }}
                        </option>
                    @endforeach
                </x-ui.select>

                <label for="filtre-recherche" class="sr-only">Rechercher une mission ou un bénévole</label>

                <div class="relative sm:flex-1">
                    <svg class="pointer-events-none absolute start-4 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.45 4.39l3.08 3.08a.75.75 0 11-1.06 1.06l-3.08-3.08A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>

                    {{-- La saisie libre attend une pause dans la frappe ; la
                         liste deroulante part des la selection. --}}
                    <x-text-input id="filtre-recherche" name="name" type="search"
                                  :value="$criteria['name']"
                                  placeholder="Rechercher mission ou bénévole…"
                                  class="ps-10"
                                  x-on:input.debounce.400ms="refresh()" />
                </div>

                <x-ui.button variant="primary" type="submit" x-ref="submit">Filtrer</x-ui.button>

                {{-- L'export suit la mission filtree et le jour affiche. --}}
                <div id="export-planning" class="shrink-0">
                    @if ($selectedDay !== null)
                        <x-admin.export-dialog
                            :datasets="[ExportDataset::Missions, ExportDataset::Planning]"
                            :criteria="['mission' => $criteria['mission']]"
                            :missions="$missions"
                            :day="$selectedDay"
                            class="[&>button]:w-full" />
                    @endif
                </div>
            </div>

            @if ($errors->hasAny(['mission', 'name']))
                <p class="w-full px-2 text-sm text-danger">{{ $errors->first('mission') ?: $errors->first('name') }}</p>
            @endif
        </form>

        {{-- Zone rejouee par le filtrage, et par les onglets de jour : un
             changement de jour ne recharge pas la page, la journee glisse
             depuis le cote choisi (`follow`, live-filters.js). --}}
        <div id="resultats-planning" class="planning-day space-y-5 sm:space-y-6" aria-live="polite">

        @if ($selectedDay === null)
            <x-ui.empty title="Cette édition ne couvre aucune journée">
                Vérifiez les dates de début et de fin de l'édition.
            </x-ui.empty>
        @else
            {{-- Bandeau du jour, comme cote benevole : la date, et le
                 remplissage de la journee en clair. --}}
            <div class="relative flex min-h-[10rem] flex-col justify-end overflow-hidden rounded-3xl bg-gradient-to-r from-plum to-primary p-5 text-white sm:p-6">
                <img src="{{ asset('images/bandeau-scene.jpg') }}" alt="" decoding="async"
                     class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-overlay">

                <div class="relative flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-white/80">
                            Jour {{ $days->search(fn ($day) => $day->isSameDay($selectedDay)) + 1 }} sur {{ $days->count() }}
                        </p>
                        <h2 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ ucfirst($selectedDay->translatedFormat('l j F Y')) }}</h2>
                    </div>

                    @if ($dayFillRate)
                        @php $dayStaffing = StaffingLevel::fromCounts($dayFillRate['taken'], $dayFillRate['capacity']); @endphp

                        {{-- Sur le degrade, une couleur seule se perdrait : le
                             niveau de la journee tient dans une pastille blanche. --}}
                        <div class="w-full rounded-2xl bg-white/15 px-4 py-3 backdrop-blur-md tabular-grid sm:w-72">
                            <p class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-xl font-extrabold leading-none">{{ $dayFillRate['rate'] }} % de remplissage</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-bold {{ $dayStaffing->textClass() }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $dayStaffing->barClass() }}" aria-hidden="true"></span>
                                    {{ $dayStaffing->label() }}
                                </span>
                            </p>
                            <p class="mt-1 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-white/80">
                                {{ $dayFillRate['remaining'] }} / {{ $dayFillRate['capacity'] }} places encore libres
                            </p>
                            {{-- La barre des places libres : pleine au depart, elle se
                                 vide a mesure que la journee se remplit. --}}
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/25">
                                <div class="h-full rounded-full bg-white" style="width:{{ $dayFillRate['capacity'] > 0 ? (int) round($dayFillRate['remaining'] / $dayFillRate['capacity'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- La legende des couleurs, une fois pour toute la journee. --}}
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-zinc-500">
                <span class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-gauge-free/20 ring-1 ring-inset ring-gauge-free/40" aria-hidden="true"></span>
                    Planning validé
                </span>
                <span class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-danger/10 ring-1 ring-inset ring-danger/30" aria-hidden="true"></span>
                    Planning non validé
                </span>
                <span class="hidden h-4 w-px bg-zinc-300 sm:block" aria-hidden="true"></span>
                <span>Jauge : un segment par place libre, <span class="font-semibold text-danger">à pourvoir</span> · <span class="font-semibold text-gauge-tight">en cours</span> · <span class="font-semibold text-gauge-free">complet</span></span>
            </div>

            {{-- Puis les tranches horaires, repliables : la premiere s'ouvre
                 d'emblee, les autres a la demande. Filtree sur une mission ou
                 par une recherche, la journee est courte et tout s'ouvre. --}}
            <div class="space-y-3">
            @foreach ($timeSlots as $timeSlot)
                @php
                    $shifts = $shiftsByTimeSlot->get($timeSlot->id, collect());
                    $enrolled = $shifts->sum(fn ($shift) => $shift->volunteers->count());
                    $places = $shifts->sum('capacity');
                    $staffing = StaffingLevel::fromCounts($enrolled, $places);
                @endphp

                <details @if ($loop->first || $criteria['mission'] || $criteria['name']) open @endif
                         class="group rounded-2xl bg-white/70 ring-1 ring-zinc-900/5 open:bg-transparent open:ring-0">
                    <summary class="flex min-h-touch cursor-pointer list-none items-center justify-between gap-3 rounded-2xl bg-white px-4 py-3 shadow-card ring-1 ring-zinc-900/5 transition hover:ring-zinc-900/10 sm:px-5 [&::-webkit-details-marker]:hidden">
                        <span class="min-w-0">
                            <span class="tabular-grid flex items-center gap-2.5 text-lg font-extrabold tracking-tight text-zinc-900 sm:text-xl">
                                <span class="h-2 w-2 shrink-0 rounded-full bg-primary-bright" aria-hidden="true"></span>
                                {{ $timeSlot->label() }}
                            </span>
                            <span class="mt-0.5 block ps-[1.125rem] text-sm text-zinc-500 tabular-grid">
                                {{ $enrolled }} bénévole{{ $enrolled > 1 ? 's' : '' }}
                                sur {{ $places }} place{{ $places > 1 ? 's' : '' }}
                                · {{ $shifts->count() }} mission{{ $shifts->count() > 1 ? 's' : '' }}
                            </span>
                        </span>

                        <span class="flex shrink-0 items-center gap-2">
                            @if ($places > 0)
                                <x-ui.badge :tone="$staffing->tone()" dot class="hidden sm:inline-flex">
                                    {{ (int) round($enrolled / $places * 100) }} % pourvu
                                </x-ui.badge>
                            @endif

                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition duration-300 group-open:rotate-180" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    </summary>

                    <div class="slot-body pb-2 pt-3">
                        @if ($shifts->isEmpty())
                            <p class="rounded-2xl border border-dashed border-zinc-300 p-4 text-sm text-zinc-500">
                                @if ($criteria['name'])
                                    Aucun créneau ne correspond à la recherche sur cette tranche horaire.
                                @else
                                    Aucune mission n'est ouverte sur cette tranche horaire.
                                @endif
                            </p>
                        @else
                            <div class="grid gap-3 sm:gap-4 md:grid-cols-2 lg:grid-cols-3">
                                @foreach ($shifts as $shift)
                                    <x-admin.shift-roster :shift="$shift" />
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>
            @endforeach
            </div>
        @endif
        </div>
    @endif
</x-admin-layout>
