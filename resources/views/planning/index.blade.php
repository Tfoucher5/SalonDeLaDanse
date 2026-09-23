<x-app-layout title="Planning">
    <x-slot name="header">
        <x-ui.page-header
            title="Le planning"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Choisissez vos créneaux jour par jour.' : 'Aucune édition n\'est ouverte pour le moment.'">
            @if ($edition)
                <x-slot name="actions">
                    <x-ui.badge :tone="$state->tone()">{{ $state->label() }}</x-ui.badge>
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
            <x-ui.alert tone="primary">{{ session('status') }}</x-ui.alert>
        @endif

        @error('shift')
            <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
        @enderror

        {{-- Ce que le benevole a deja retenu, tous jours confondus. --}}
        <x-ui.card title="Mes créneaux" subtitle="Tous jours confondus.">
            @php
                $booked = $bookedShiftIds->count();
                $maximum = $edition->max_slots_per_volunteer;
                $minimum = $edition->min_slots_per_volunteer;
                $filled = $maximum > 0 ? (int) round(min($booked, $maximum) / $maximum * 100) : 0;
            @endphp

            <p class="tabular-grid text-zinc-900">
                {{ $booked }} créneau{{ $booked > 1 ? 'x' : '' }}
                retenu{{ $booked > 1 ? 's' : '' }} sur {{ $maximum }} possibles.

                @if ($booked < $minimum)
                    Il vous en faut {{ $minimum }} au minimum pour valider votre planning.
                @endif
            </p>

            <div class="mt-3 h-1.5 overflow-hidden rounded-md bg-zinc-200"
                 role="img"
                 aria-label="{{ $booked }} créneau{{ $booked > 1 ? 'x' : '' }} sur {{ $maximum }}">
                <div class="h-full rounded-md bg-primary" style="width: {{ $filled }}%"></div>
            </div>

            @unless ($state->isEditable())
                <x-ui.alert class="mt-4">{{ $state->description() }}</x-ui.alert>
            @endunless
        </x-ui.card>

        {{-- Navigation par jour : le premier niveau de lecture sur mobile. --}}
        <nav class="flex flex-wrap gap-2" aria-label="Jours du Salon">
            @foreach ($days as $day)
                <x-planning.day-tab :day="$day" :selected="$selectedDay?->isSameDay($day) ?? false" />
            @endforeach
        </nav>

        {{-- Puis les tranches horaires, et dans chacune les missions. --}}
        @foreach ($timeSlots as $timeSlot)
            @php $shifts = $shiftsByTimeSlot->get($timeSlot->id, collect()); @endphp

            <section class="space-y-3">
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-zinc-200 pb-2">
                    <h2 class="tabular-grid text-lg font-semibold text-zinc-900">{{ $timeSlot->label() }}</h2>

                    <p class="text-sm text-zinc-500">
                        {{ $shifts->count() }} mission{{ $shifts->count() > 1 ? 's' : '' }}
                    </p>
                </div>

                @if ($shifts->isEmpty())
                    <p class="rounded-lg border border-dashed border-zinc-200 bg-white p-4 text-sm text-zinc-500">
                        Aucune mission n'est ouverte sur cette tranche horaire.
                    </p>
                @else
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
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
