@use('Illuminate\Support\Carbon')
@use('App\Enums\StaffingLevel')

<x-admin-layout title="Vue d'ensemble">
    <x-slot name="header">
        <x-ui.page-header
            title="Vue d'ensemble"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Où en sont les bénévoles, et où en est le remplissage.' : 'Aucune édition n\'est ouverte pour le moment.'">
            @if ($edition)
                <x-slot name="actions">
                    <x-ui.button :href="route('admin.planning')">Planning du Salon</x-ui.button>
                    <x-ui.button :href="route('admin.volunteers.index')">Chercher un bénévole</x-ui.button>
                </x-slot>
            @endif
        </x-ui.page-header>
    </x-slot>

    @if ($edition === null)
        <x-ui.empty title="Aucune édition active">
            Les compteurs s'afficheront ici dès qu'une édition du Salon sera ouverte.
        </x-ui.empty>
    @else
        {{-- Le bandeau de tete, sur le modele de « Mes creneaux » cote
             benevole : l'anneau dit le remplissage global, les tuiles le
             dispositif humain. Chaque tuile mene a la liste qu'elle resume. --}}
        @php $globalStaffing = StaffingLevel::fromCounts($fillRate['taken'], $fillRate['capacity']); @endphp

        <section class="relative overflow-hidden rounded-3xl bg-white p-5 shadow-card ring-1 ring-zinc-900/5 sm:p-6" aria-labelledby="remplissage-global">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-primary-soft blur-2xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-12 -left-12 h-56 w-56 rounded-full bg-gauge-free/10 blur-2xl" aria-hidden="true"></div>

            <div class="relative grid gap-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:items-center">
                <div class="flex items-center gap-4 sm:gap-6">
                    <div class="relative flex h-28 w-28 shrink-0 items-center justify-center rounded-2xl bg-zinc-50">
                        <svg class="h-24 w-24 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="3.5" class="stroke-zinc-200" />
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="3.5" stroke-linecap="round"
                                    class="progress-ring {{ $globalStaffing->strokeClass() }}" stroke-dasharray="{{ min(100, $fillRate['rate']) }}, 100" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center tabular-grid">
                            <span class="text-2xl font-extrabold leading-none {{ $globalStaffing->textClass() }}">{{ $fillRate['rate'] }} %</span>
                            <span class="mt-1 text-[0.625rem] font-bold uppercase tracking-[0.08em] text-zinc-500">Rempli</span>
                        </div>
                    </div>

                    <div class="min-w-0 space-y-1.5">
                        <h2 id="remplissage-global" class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">
                            Remplissage global
                        </h2>

                        <p class="tabular-grid text-[0.9375rem] text-zinc-900">
                            <strong class="font-bold">{{ $fillRate['rate'] }} % de remplissage</strong> :
                            {{ $fillRate['taken'] }} place{{ $fillRate['taken'] > 1 ? 's' : '' }} prise{{ $fillRate['taken'] > 1 ? 's' : '' }}
                            sur {{ $fillRate['capacity'] }} offertes.
                        </p>

                        <p class="text-sm text-zinc-500">
                            Encore {{ $fillRate['remaining'] }} place{{ $fillRate['remaining'] > 1 ? 's' : '' }} à pourvoir, missions restreintes comprises.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <x-ui.stat
                        label="Bénévoles attendus"
                        :value="$headcount['expected']"
                        :hint="$headcount['codes_left'].' code'.($headcount['codes_left'] > 1 ? 's' : '').' encore disponible'.($headcount['codes_left'] > 1 ? 's' : '')" />

                    <x-ui.stat
                        label="Comptes créés"
                        :value="$headcount['accounts']"
                        :target="$headcount['expected']"
                        :hint="'sur '.$headcount['expected'].' attendus'"
                        :href="route('admin.volunteers.index')" />

                    <x-ui.stat
                        label="Plannings validés"
                        :value="$headcount['validated']"
                        :target="$headcount['accounts']"
                        :hint="'sur '.$headcount['accounts'].' comptes créés'"
                        :href="route('admin.volunteers.index', ['status' => 'validated'])" />

                    <x-ui.stat
                        label="Plannings non validés"
                        :value="$headcount['pending']"
                        :level="$headcount['pending'] > 0 ? StaffingLevel::Critical : StaffingLevel::Staffed"
                        hint="comptes créés, planning non figé"
                        :href="route('admin.volunteers.index', ['status' => 'pending'])" />
                </div>
            </div>
        </section>

        <div class="grid items-start gap-5 sm:gap-6 lg:grid-cols-2">
            <x-ui.card title="Par jour" kicker="Remplissage" subtitle="Ouvrez une journée pour voir qui est inscrit où.">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                    </svg>
                </x-slot>

                @if ($byDay->isEmpty())
                    <x-ui.empty title="Aucun créneau en base">
                        Les créneaux se créent avec les missions et les tranches horaires de l'édition.
                    </x-ui.empty>
                @else
                    <ul class="space-y-2.5">
                        @foreach ($byDay as $row)
                            @php $date = Carbon::parse($row['key']); @endphp

                            <li>
                                <a href="{{ route('admin.planning', ['day' => $row['key']]) }}"
                                   class="group flex items-center gap-3 rounded-xl bg-zinc-50 p-3 transition hover:bg-white hover:shadow-card hover:ring-1 hover:ring-zinc-900/5">
                                    <span class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-white shadow-card tabular-grid">
                                        <span class="text-[0.625rem] font-bold uppercase leading-none text-primary">{{ $date->translatedFormat('D') }}</span>
                                        <span class="text-base font-extrabold leading-tight text-zinc-900">{{ $date->format('j') }}</span>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-baseline justify-between gap-2">
                                            <span class="font-semibold text-zinc-900">{{ $row['label'] }}</span>
                                            <span class="tabular-grid text-sm text-zinc-500">{{ $row['taken'] }} / {{ $row['capacity'] }} places</span>
                                        </span>

                                        <x-ui.gauge
                                            class="mt-1"
                                            :level="StaffingLevel::fromCounts($row['taken'], $row['capacity'])->tone()"
                                            :label="$row['rate'].' % de remplissage'"
                                            :remaining="$row['remaining']"
                                            :capacity="$row['capacity']" />
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card title="Par mission" kicker="Remplissage" subtitle="Les missions restreintes sont signalées.">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 5h6M9 3h6a1 1 0 011 1v1h2a1 1 0 011 1v14a1 1 0 01-1 1H6a1 1 0 01-1-1V6a1 1 0 011-1h2V4a1 1 0 011-1zm0 10l2 2 4-4" />
                    </svg>
                </x-slot>

                @if ($byMission->isEmpty())
                    <x-ui.empty title="Aucune mission en base">
                        Les missions de l'édition n'ont pas encore été créées.
                    </x-ui.empty>
                @else
                    <ul class="divide-y divide-zinc-200">
                        @foreach ($byMission as $row)
                            <li>
                                <a href="{{ route('admin.planning', ['mission' => $row['key']]) }}"
                                   class="-mx-2 block rounded-xl px-2 py-3 transition hover:bg-zinc-50">
                                    <span class="flex flex-wrap items-baseline justify-between gap-2">
                                        <span class="font-semibold text-zinc-900">
                                            {{ $row['label'] }}

                                            @if ($row['restricted'])
                                                <x-ui.badge class="ms-1 align-middle">Restreinte</x-ui.badge>
                                            @endif
                                        </span>

                                        <span class="tabular-grid text-sm text-zinc-500">{{ $row['taken'] }} / {{ $row['capacity'] }} places</span>
                                    </span>

                                    <x-ui.gauge
                                        class="mt-1"
                                        :level="StaffingLevel::fromCounts($row['taken'], $row['capacity'])->tone()"
                                        :label="$row['rate'].' % de remplissage'"
                                        :remaining="$row['remaining']"
                                        :capacity="$row['capacity']" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    @endif
</x-admin-layout>
