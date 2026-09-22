<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold text-zinc-900">Le planning</h2>
        <p class="mt-1 text-sm text-zinc-500">
            @if ($edition)
                {{ $edition->name }} — {{ $state->label() }}
            @else
                Aucune édition n'est ouverte pour le moment.
            @endif
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($edition === null)
                <section class="bg-white border border-zinc-200 rounded-lg p-6">
                    <p class="text-zinc-900">
                        Le planning s'affichera ici dès qu'une édition du Salon sera ouverte.
                    </p>
                </section>
            @else
                {{-- Ce que le bénévole a déjà retenu, tous jours confondus. --}}
                <section class="bg-white border border-zinc-200 rounded-lg p-6 tabular-grid">
                    <h3 class="text-lg font-semibold text-zinc-900">Mes créneaux</h3>
                    <p class="mt-2 text-zinc-900">
                        {{ $bookedShiftIds->count() }} créneau{{ $bookedShiftIds->count() > 1 ? 'x' : '' }}
                        retenu{{ $bookedShiftIds->count() > 1 ? 's' : '' }}
                        sur {{ $edition->max_slots_per_volunteer }} possibles.
                    </p>

                    @if ($blockingReason)
                        <p class="mt-4 flex items-start gap-2 rounded-md bg-zinc-100 p-4 text-sm text-zinc-500">
                            <span aria-hidden="true">&#9432;</span>
                            <span>{{ $state->description() }}</span>
                        </p>
                    @endif
                </section>

                {{-- Navigation par jour : le premier niveau de lecture sur mobile. --}}
                <nav class="flex flex-wrap gap-2" aria-label="Jours du Salon">
                    @foreach ($days as $day)
                        @php $isSelected = $selectedDay?->isSameDay($day) ?? false; @endphp

                        <a href="{{ route('planning.index', ['day' => $day->toDateString()]) }}"
                           @if ($isSelected) aria-current="page" @endif
                           class="flex h-14 flex-1 basis-24 flex-col items-center justify-center rounded-md border tabular-grid {{ $isSelected ? 'border-primary bg-primary text-white' : 'border-zinc-200 bg-white text-zinc-900 hover:bg-zinc-100' }}">
                            <span class="text-sm font-medium">{{ ucfirst($day->translatedFormat('D')) }}</span>
                            <span class="text-xs">{{ $day->translatedFormat('j M') }}</span>
                        </a>
                    @endforeach
                </nav>

                {{-- Puis les tranches horaires, et dans chacune les missions. --}}
                @foreach ($timeSlots as $timeSlot)
                    @php $shifts = $shiftsByTimeSlot->get($timeSlot->id, collect()); @endphp

                    <section>
                        <h3 class="text-lg font-semibold text-zinc-900 tabular-grid">{{ $timeSlot->label() }}</h3>

                        @if ($shifts->isEmpty())
                            <p class="mt-3 rounded-lg border border-zinc-200 bg-white p-4 text-sm text-zinc-500">
                                Aucune mission n'est ouverte sur cette tranche horaire.
                            </p>
                        @else
                            <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                                @foreach ($shifts as $shift)
                                    <x-planning.shift-card
                                        :shift="$shift"
                                        :booked="$bookedShiftIds->contains($shift->id)"
                                        :reason="$blockingReason" />
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
