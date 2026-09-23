<x-app-layout title="Planning">
    <x-slot name="header">
        <x-ui.page-header
            title="Le planning"
            :back="route('dashboard')"
            back-label="Tableau de bord"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Choisissez vos créneaux jour par jour.' : 'Aucune édition n\'est ouverte pour le moment.'">
            @if ($edition)
                <x-slot name="actions">
                    <div class="flex items-center gap-3 rounded-2xl bg-white px-4 py-2.5 shadow-card ring-1 ring-zinc-900/5">
                        <span class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">État actuel</span>
                        <x-ui.badge :tone="$state->tone()" dot>{{ $state->label() }}</x-ui.badge>
                    </div>
                </x-slot>
            @endif
        </x-ui.page-header>
    </x-slot>

    @if ($edition === null)
        <x-ui.empty title="Le planning n'est pas encore ouvert">
            Il s'affichera ici dès qu'une édition du Salon sera ouverte aux inscriptions.
        </x-ui.empty>
    @else
        {{-- Reserver, retirer et changer de jour se font sans recharger la page
             (resources/js/planning.js) ; l'issue de l'action s'affiche en
             notification. Sans JavaScript, formulaires et liens fonctionnent
             normalement. --}}
        <div x-data="planning" x-on:submit="submit($event)" x-on:click="openDay($event)" class="space-y-5 sm:space-y-6">
        @php
            $booked = $bookedShiftIds->count();
            $maximum = $edition->max_slots_per_volunteer;
            $minimum = $edition->min_slots_per_volunteer;
            $ratio = $maximum > 0 ? min($booked, $maximum) / $maximum : 0;
        @endphp

        {{-- Ce que le benevole a deja retenu, tous jours confondus. --}}
        <section id="planning-summary" class="relative overflow-hidden rounded-3xl bg-white p-5 shadow-card ring-1 ring-zinc-900/5 sm:p-6" aria-labelledby="mes-creneaux">
            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-primary-soft blur-2xl" aria-hidden="true"></div>

            <div class="relative flex items-center gap-4 sm:gap-6">
                <div class="relative flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-zinc-50 sm:h-28 sm:w-28">
                    <svg class="h-20 w-20 -rotate-90 sm:h-24 sm:w-24" viewBox="0 0 36 36" aria-hidden="true">
                        <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="3.5" class="stroke-zinc-200" />
                        <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="3.5" stroke-linecap="round"
                                class="stroke-primary" stroke-dasharray="{{ round($ratio * 100, 1) }}, 100" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center tabular-grid">
                        <span class="text-xl font-extrabold leading-none text-zinc-900">{{ $booked }}/{{ $maximum }}</span>
                        <span class="mt-0.5 text-[0.625rem] font-bold uppercase tracking-[0.08em] text-zinc-500">Créneaux</span>
                    </div>
                </div>

                <div class="min-w-0 flex-1 space-y-2">
                    <h2 id="mes-creneaux" class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">
                        Mes créneaux — tous jours confondus
                    </h2>

                    <p class="tabular-grid text-sm text-zinc-900 sm:text-[0.9375rem]">
                        <strong class="font-bold">{{ $booked }} créneau{{ $booked > 1 ? 'x' : '' }} retenu{{ $booked > 1 ? 's' : '' }}</strong>
                        sur {{ $maximum }} possibles.

                        @if ($booked < $minimum)
                            Vous vous êtes engagé sur {{ $minimum }} créneau{{ $minimum > 1 ? 'x' : '' }} au minimum.
                        @endif
                    </p>

                    <div class="flex max-w-sm items-center gap-1.5" role="img"
                         aria-label="{{ $booked }} créneau{{ $booked > 1 ? 'x' : '' }} sur {{ $maximum }}">
                        @for ($segment = 1; $segment <= $maximum; $segment++)
                            <span @class([
                                'h-2 flex-1 rounded-full',
                                'bg-primary' => $segment <= $booked,
                                'bg-zinc-200' => $segment > $booked,
                            ])></span>
                        @endfor
                    </div>
                </div>
            </div>

            @unless ($state->isEditable())
                <x-ui.alert class="relative mt-5">{{ $state->description() }}</x-ui.alert>
            @endunless

            <div class="relative mt-5 border-t border-zinc-200 pt-4">
                <x-ui.button :href="route('planning.summary')" size="touch" class="w-full sm:w-auto">
                    Ma fiche récapitulative
                </x-ui.button>
            </div>
        </section>

        {{-- Navigation par jour : un simple changement d'onglet, sans recharger
             la page (voir resources/js/planning.js). Collee sous la barre du
             haut pour rester a portee pendant le defilement. --}}
        <nav id="planning-days" class="sticky top-16 z-30 -mx-4 bg-zinc-50/90 px-4 py-2 backdrop-blur-md sm:mx-0 sm:px-0" aria-label="Jours du Salon">
            <div class="flex gap-1 rounded-2xl bg-zinc-100 p-1.5 md:inline-flex md:min-w-[26rem]">
                @foreach ($days as $index => $day)
                    <x-planning.day-tab :day="$day" :index="$index" :selected="$selectedDay?->isSameDay($day) ?? false" />
                @endforeach
            </div>
        </nav>

        {{-- Tout ce qui depend du jour affiche : remplace d'un bloc au
             changement d'onglet, avec une transition dans le sens du geste. --}}
        <div id="planning-day" class="planning-day space-y-5 sm:space-y-6">
            @if ($selectedDay)
                @php $openShifts = $shiftsByTimeSlot->flatten(1)->count(); @endphp

                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-plum to-primary p-5 text-white sm:p-6">
                    <div class="relative flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-white/80">
                                Jour {{ $days->search(fn ($day) => $day->isSameDay($selectedDay)) + 1 }} sur {{ $days->count() }}
                            </p>
                            <h2 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ ucfirst($selectedDay->translatedFormat('l j F Y')) }}</h2>
                        </div>

                        <p class="flex items-center gap-2 rounded-2xl bg-white/15 px-4 py-2 backdrop-blur-md tabular-grid">
                            <span class="text-xl font-extrabold leading-none">{{ $openShifts }}</span>
                            <span class="text-[0.6875rem] font-bold uppercase tracking-[0.08em]">créneau{{ $openShifts > 1 ? 'x' : '' }} ouvert{{ $openShifts > 1 ? 's' : '' }}</span>
                        </p>
                    </div>
                </div>
            @endif

            {{-- Les tranches horaires, repliables pour que la journee tienne sur
                 un ecran de telephone. S'ouvrent d'emblee : la tranche de la
                 derniere action, celles ou le benevole est inscrit, et a defaut
                 la premiere. Les cartes gardent toujours l'ordre des missions :
                 rien ne bouge sous le doigt apres une reservation. --}}
            @php
                $focusShiftId = session('focus_shift');
                $slotIsOpen = fn ($shifts): bool => $shifts->contains(
                    fn ($shift): bool => $shift->id === $focusShiftId || $bookedShiftIds->contains($shift->id)
                );
                $firstOpenSlotId = $timeSlots->first(fn ($timeSlot) => $slotIsOpen($shiftsByTimeSlot->get($timeSlot->id, collect())))?->id
                    ?? $timeSlots->first(fn ($timeSlot) => $shiftsByTimeSlot->has($timeSlot->id))?->id;
            @endphp

            <div class="space-y-3">
                @foreach ($timeSlots as $timeSlot)
                    @php
                        $shifts = $shiftsByTimeSlot->get($timeSlot->id, collect());
                        $openCount = $shifts->filter(fn ($shift): bool => ($motives[$shift->id] ?? null) === null && ! $bookedShiftIds->contains($shift->id))->count();
                        $bookedHere = $shifts->contains(fn ($shift): bool => $bookedShiftIds->contains($shift->id));
                        $isOpen = $slotIsOpen($shifts) || $timeSlot->id === $firstOpenSlotId;
                    @endphp

                    <details @if ($isOpen) open @endif
                             class="group rounded-2xl bg-white/70 ring-1 ring-zinc-900/5 open:bg-transparent open:ring-0">
                        <summary id="slot-summary-{{ $timeSlot->id }}"
                                 class="flex min-h-touch cursor-pointer list-none items-center justify-between gap-3 rounded-2xl bg-white px-4 py-3 shadow-card ring-1 ring-zinc-900/5 transition hover:ring-zinc-900/10 sm:px-5 [&::-webkit-details-marker]:hidden">
                            <span class="min-w-0">
                                <span class="tabular-grid flex items-center gap-2.5 text-lg font-extrabold tracking-tight text-zinc-900 sm:text-xl">
                                    <span class="h-2 w-2 shrink-0 rounded-full bg-primary-bright" aria-hidden="true"></span>
                                    {{ $timeSlot->label() }}
                                </span>
                                <span class="mt-0.5 block ps-[1.125rem] text-sm text-zinc-500">
                                    {{ $shifts->count() }} mission{{ $shifts->count() > 1 ? 's' : '' }}
                                    · {{ $openCount }} ouverte{{ $openCount > 1 ? 's' : '' }} pour vous
                                </span>
                            </span>

                            <span class="flex shrink-0 items-center gap-2">
                                @if ($bookedHere)
                                    <x-ui.badge tone="primary">Inscrit</x-ui.badge>
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
                                    Aucune mission n'est ouverte sur cette tranche horaire.
                                </p>
                            @else
                                <div class="grid items-start gap-3 sm:gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($shifts as $shift)
                                        <x-planning.shift-card
                                            :shift="$shift"
                                            :booked="$bookedShiftIds->contains($shift->id)"
                                            :motive="$motives[$shift->id] ?? null"
                                            :editable="$state->isEditable()"
                                            :focused="$shift->id === $focusShiftId" />
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
        </div>
    @endif
</x-app-layout>
