<x-app-layout title="Planning">
    <x-slot name="header">
        <x-ui.page-header
            title="Le planning"
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
        {{-- L'issue de la derniere action, avant tout le reste : une regle qui
             refuse doit se lire sans chercher. --}}
        @if (session('status'))
            <x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>
        @endif

        @error('shift')
            <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
        @enderror

        @php
            $booked = $bookedShiftIds->count();
            $maximum = $edition->max_slots_per_volunteer;
            $minimum = $edition->min_slots_per_volunteer;
            $ratio = $maximum > 0 ? min($booked, $maximum) / $maximum : 0;
        @endphp

        {{-- Ce que le benevole a deja retenu, tous jours confondus. --}}
        <section class="relative overflow-hidden rounded-3xl bg-white p-5 shadow-card ring-1 ring-zinc-900/5 sm:p-6" aria-labelledby="mes-creneaux">
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

        {{-- Navigation par jour : le premier niveau de lecture sur mobile, colle
             sous la barre du haut pour rester a portee pendant le defilement. --}}
        <nav class="sticky top-16 z-30 -mx-4 bg-zinc-50/90 px-4 py-2 backdrop-blur-md sm:mx-0 sm:px-0" aria-label="Jours du Salon">
            <div class="flex gap-1 rounded-2xl bg-zinc-100 p-1.5 md:inline-flex md:min-w-[26rem]">
                @foreach ($days as $day)
                    <x-planning.day-tab :day="$day" :selected="$selectedDay?->isSameDay($day) ?? false" />
                @endforeach
            </div>
        </nav>

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

        {{-- Puis les tranches horaires, et dans chacune les missions. --}}
        @foreach ($timeSlots as $timeSlot)
            @php $shifts = $shiftsByTimeSlot->get($timeSlot->id, collect()); @endphp

            <section class="space-y-3 pt-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="tabular-grid flex items-center gap-2.5 text-xl font-extrabold tracking-tight text-zinc-900 sm:text-2xl">
                        <span class="h-2 w-2 rounded-full bg-primary-bright" aria-hidden="true"></span>
                        {{ $timeSlot->label() }}
                    </h2>

                    <p class="text-sm font-medium text-zinc-500">
                        {{ $shifts->count() }} mission{{ $shifts->count() > 1 ? 's' : '' }}
                    </p>
                </div>

                @if ($shifts->isEmpty())
                    <p class="rounded-2xl border border-dashed border-zinc-300 p-4 text-sm text-zinc-500">
                        Aucune mission n'est ouverte sur cette tranche horaire.
                    </p>
                @else
                    <div class="grid gap-3 sm:gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($shifts as $shift)
                            <x-planning.shift-card
                                :shift="$shift"
                                :booked="$bookedShiftIds->contains($shift->id)"
                                :motive="$motives[$shift->id] ?? null"
                                :editable="$state->isEditable()" />
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    @endif
</x-app-layout>
