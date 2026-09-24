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
        {{-- Le bandeau de tete (maquette « Vue d'ensemble Admin ») : l'anneau
             dit le remplissage global, les cartes le dispositif humain. Toutes
             suivent l'echelle continue de pourvoi : plus c'est plein, plus
             c'est vert. Chaque carte mene a la liste qu'elle resume. --}}
        @php
            $accountsRate = $headcount['expected'] > 0 ? (int) round($headcount['accounts'] / $headcount['expected'] * 100) : 0;
            $validatedRate = $headcount['accounts'] > 0 ? (int) round($headcount['validated'] / $headcount['accounts'] * 100) : 0;
        @endphp

        <div class="grid gap-4 sm:gap-5 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
            <section class="staffing-scale relative flex items-center overflow-hidden rounded-3xl bg-white p-5 shadow-card ring-1 ring-zinc-900/5 sm:p-6"
                     style="--fill: {{ min(100, $fillRate['rate']) }}"
                     aria-labelledby="remplissage-global">
                <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-primary-soft blur-2xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-56 w-56 rounded-full bg-gauge-free/10 blur-2xl" aria-hidden="true"></div>

                <div class="relative flex w-full flex-col items-center gap-5 text-center sm:flex-row sm:text-left">
                    <div class="relative flex h-36 w-36 shrink-0 items-center justify-center">
                        <svg class="h-36 w-36 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="3" class="stroke-zinc-200" />
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="3" stroke-linecap="round"
                                    class="progress-ring stroke-staffing" stroke-dasharray="{{ min(100, $fillRate['rate']) }}, 100" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center tabular-grid">
                            <span class="text-3xl font-extrabold leading-none tracking-tight text-zinc-900">{{ $fillRate['rate'] }} %</span>
                            <span class="mt-1 text-[0.625rem] font-bold uppercase tracking-[0.08em] text-staffing">Rempli</span>
                        </div>
                    </div>

                    <div class="min-w-0 space-y-2">
                        <h2 id="remplissage-global" class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">
                            Remplissage global
                        </h2>

                        <p class="tabular-grid text-xl font-bold tracking-tight text-zinc-900">
                            {{ $fillRate['taken'] }} place{{ $fillRate['taken'] > 1 ? 's' : '' }} prise{{ $fillRate['taken'] > 1 ? 's' : '' }}
                            <span class="font-medium text-zinc-500">/ {{ $fillRate['capacity'] }}</span>
                        </p>

                        <p class="text-sm text-zinc-500">
                            Encore <strong class="font-bold text-zinc-900">{{ $fillRate['remaining'] }} place{{ $fillRate['remaining'] > 1 ? 's' : '' }}</strong>
                            à pourvoir sur {{ $byDay->count() }} jour{{ $byDay->count() > 1 ? 's' : '' }}, missions restreintes comprises.
                        </p>

                        <span class="bg-staffing-soft inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold text-staffing">
                            <span class="bg-staffing h-1.5 w-1.5 rounded-full" aria-hidden="true"></span>
                            {{ StaffingLevel::fromCounts($fillRate['taken'], $fillRate['capacity'])->label() }}
                        </span>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-2 sm:gap-5">
                <x-admin.kpi
                    label="Bénévoles attendus"
                    :value="$headcount['expected']"
                    unit="objectif cible"
                    detail-label="Codes d'invitation"
                    :detail-value="$headcount['codes_left'].' disponible'.($headcount['codes_left'] > 1 ? 's' : '')">
                    <x-slot name="icon">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 20v-1a4 4 0 00-4-4H7a4 4 0 00-4 4v1m18 0v-1a4 4 0 00-3-3.87M14 4.13a4 4 0 010 7.75M14 8a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </x-slot>
                </x-admin.kpi>

                <x-admin.kpi
                    label="Comptes créés"
                    :value="$headcount['accounts']"
                    :unit="$accountsRate.' % de l\'effectif'"
                    :rate="$accountsRate"
                    bar
                    :hint="'sur '.$headcount['expected'].' attendus'"
                    :href="route('admin.volunteers.index')">
                    <x-slot name="icon">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 19v-1a4 4 0 00-4-4H6a4 4 0 00-4 4v1m15-8l2 2 4-4M12.5 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </x-slot>
                </x-admin.kpi>

                <x-admin.kpi
                    label="Plannings validés"
                    :value="$headcount['validated']"
                    unit="bénévoles figés"
                    :rate="$validatedRate"
                    detail-label="Taux de validation"
                    :detail-value="$validatedRate.' % des comptes'"
                    :href="route('admin.volunteers.index', ['status' => 'validated'])">
                    <x-slot name="icon">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-admin.kpi>

                <x-admin.kpi
                    label="Plannings non validés"
                    :value="$headcount['pending']"
                    unit="comptes non figés"
                    :tone="$headcount['pending'] > 0 ? 'danger' : 'free'"
                    :detail-label="$headcount['pending'] > 0 ? 'Relance recommandée' : 'Tous les plannings sont figés'"
                    :detail-value="$headcount['pending'] > 0 ? 'Voir la liste' : ''"
                    :href="route('admin.volunteers.index', ['status' => 'pending'])">
                    <x-slot name="icon">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm7 7v3l2 1" />
                        </svg>
                    </x-slot>
                </x-admin.kpi>
            </div>
        </div>

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
                                            level="scale"
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
                                        level="scale"
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
