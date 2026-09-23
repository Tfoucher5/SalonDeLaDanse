<x-admin-layout title="Planning">
    <x-slot name="header">
        <x-ui.page-header
            title="Planning du Salon"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Jour par jour, qui est inscrit sur quel créneau.' : 'Aucune édition n\'est ouverte pour le moment.'">
            @if ($edition && $selectedDay)
                <x-slot name="actions">
                    <x-ui.button :href="route('admin.exports.index', array_filter($criteria))" variant="ghost">
                        Exporter
                    </x-ui.button>
                </x-slot>
            @endif
        </x-ui.page-header>
    </x-slot>

    @if ($edition === null)
        <x-ui.empty title="Aucune édition active">
            Le planning s'affichera ici dès qu'une édition du Salon sera ouverte.
        </x-ui.empty>
    @else
        <x-ui.card>
            {{-- Filtrage sans clic ; sans JavaScript, le bouton reste. --}}
            <form method="GET"
                  action="{{ route('admin.planning') }}"
                  x-data="liveFilters('resultats-planning')"
                  :aria-busy="busy"
                  class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="day" value="{{ $selectedDay?->toDateString() }}">

                <x-ui.field label="Mission" for="filtre-mission" :messages="$errors->get('mission')" class="min-w-0 flex-1">
                    <x-ui.select x-on:change="refresh()" id="filtre-mission" name="mission">
                        <option value="">Toutes les missions</option>

                        @foreach ($missions as $mission)
                            <option value="{{ $mission->id }}" @selected($criteria['mission'] === $mission->id)>
                                {{ $mission->name }}{{ $mission->is_public ? '' : ' (restreinte)' }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.button variant="primary" size="touch" type="submit" x-ref="submit">Filtrer</x-ui.button>

                <span x-show="busy" x-cloak class="pb-2.5 text-sm text-zinc-500">Recherche…</span>
            </form>
        </x-ui.card>

        {{-- Zone rejouee par le filtrage. Les onglets de jour en font partie :
             ils portent le filtre courant dans leur lien. --}}
        <div id="resultats-planning" class="space-y-6" aria-live="polite">

        {{-- Navigation par jour : le premier niveau de lecture, comme chez le
             benevole. Le filtre par mission est conserve d'un jour a l'autre. --}}
        <nav class="flex flex-wrap gap-2" aria-label="Jours du Salon">
            @foreach ($days as $day)
                <x-planning.day-tab
                    :day="$day"
                    :selected="$selectedDay?->isSameDay($day) ?? false"
                    route="admin.planning"
                    :params="array_filter(['mission' => $criteria['mission']])" />
            @endforeach
        </nav>

        @if ($selectedDay === null)
            <x-ui.empty title="Cette édition ne couvre aucune journée">
                Vérifiez les dates de début et de fin de l'édition.
            </x-ui.empty>
        @else
            @if ($dayFillRate)
                <x-ui.card :title="ucfirst($selectedDay->translatedFormat('l j F Y'))"
                           :subtitle="$dayFillRate['taken'].' place'.($dayFillRate['taken'] > 1 ? 's' : '').' prise'.($dayFillRate['taken'] > 1 ? 's' : '').' sur '.$dayFillRate['capacity'].' offertes ce jour-là, missions restreintes comprises.'">
                    <x-ui.gauge
                        :level="$dayFillRate['level']"
                        :label="$dayFillRate['rate'].' % de remplissage'"
                        :remaining="$dayFillRate['remaining']"
                        :capacity="$dayFillRate['capacity']" />
                </x-ui.card>
            @endif

            {{-- Puis les tranches horaires, et dans chacune les missions. --}}
            @foreach ($timeSlots as $timeSlot)
                @php $shifts = $shiftsByTimeSlot->get($timeSlot->id, collect()); @endphp

                <section class="space-y-3">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-zinc-200 pb-2">
                        <h2 class="tabular-grid text-lg font-semibold text-zinc-900">{{ $timeSlot->label() }}</h2>

                        <p class="tabular-grid text-sm text-zinc-500">
                            {{ $shifts->sum(fn ($shift) => $shift->volunteers->count()) }}
                            bénévole{{ $shifts->sum(fn ($shift) => $shift->volunteers->count()) > 1 ? 's' : '' }}
                            sur {{ $shifts->sum('capacity') }} place{{ $shifts->sum('capacity') > 1 ? 's' : '' }}
                        </p>
                    </div>

                    @if ($shifts->isEmpty())
                        <p class="rounded-2xl border border-dashed border-zinc-200 bg-white p-4 text-sm text-zinc-500">
                            Aucune mission n'est ouverte sur cette tranche horaire.
                        </p>
                    @else
                        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($shifts as $shift)
                                <x-admin.shift-roster :shift="$shift" />
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        @endif
        </div>
    @endif
</x-admin-layout>
