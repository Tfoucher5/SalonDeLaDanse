<x-app-layout title="Ma fiche bénévole">
    <x-slot name="header">
        <x-ui.page-header
            title="Ma fiche bénévole"
            :back="route('planning.index')"
            back-label="Planning des créneaux"
            :eyebrow="$edition?->name"
            subtitle="Vos missions, vos horaires et les consignes qui vont avec.">
            <x-slot name="actions">
                <x-ui.badge :tone="$state->tone()" dot>{{ $state->label() }}</x-ui.badge>

                <x-ui.button type="button" class="print-hidden" onclick="window.print()">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 9V3h10v6M7 17H5a1 1 0 01-1-1v-6a1 1 0 011-1h14a1 1 0 011 1v6a1 1 0 01-1 1h-2M7 14h10v7H7v-7z" />
                    </svg>
                    Imprimer
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    {{-- L'etat de validation d'abord : un benevole qui imprime un brouillon
         doit savoir, avant tout le reste, que sa fiche peut encore changer. --}}
    @if ($shiftsByDay->isNotEmpty())
        @if ($state->isEditable())
            <x-ui.alert tone="attention" title="Planning pas encore validé" class="print-hidden">
                Ce planning n'est pas encore validé par l'équipe organisatrice : il peut encore changer.
            </x-ui.alert>
        @else
            <x-ui.alert tone="success">{{ $state->description() }}</x-ui.alert>
        @endif
    @endif

    <div class="grid items-start gap-5 sm:gap-6 lg:grid-cols-3">
        {{-- L'identite du benevole : la fiche circule sur papier, elle doit dire
             de qui elle parle sans la barre de navigation. --}}
        <x-ui.card class="print-keep lg:sticky lg:top-24">
            <div class="flex items-center gap-4 lg:flex-col lg:text-center">
                <x-ui.avatar :user="$user" size="h-14 w-14 text-base lg:h-20 lg:w-20 lg:text-lg" />

                <div class="min-w-0">
                    <p class="text-lg font-bold tracking-tight text-zinc-900">{{ $user->full_name }}</p>
                    <p class="truncate text-sm text-zinc-500">{{ $user->email }}</p>

                    @if ($user->phone !== '')
                        <p class="text-sm text-zinc-500 tabular-grid">{{ $user->phone }}</p>
                    @endif
                </div>
            </div>

            @php $shiftCount = $shiftsByDay->flatten(1)->count(); @endphp

            <dl class="mt-5 grid grid-cols-2 gap-3">
                <x-ui.readonly-field label="Créneaux">{{ $shiftCount }}</x-ui.readonly-field>
                <x-ui.readonly-field label="Jours">{{ $shiftsByDay->count() }}</x-ui.readonly-field>
            </dl>
        </x-ui.card>

        <div class="space-y-6 lg:col-span-2">
            @if ($shiftsByDay->isEmpty())
                <x-ui.empty title="Votre planning est encore vide">
                    Choisissez vos créneaux dans le planning : ils apparaîtront ici, prêts à imprimer.

                    <div class="mt-4 print-hidden">
                        <x-ui.button :href="route('planning.index')" variant="primary" size="touch">
                            Composer mon planning
                        </x-ui.button>
                    </div>
                </x-ui.empty>
            @else
                @foreach ($shiftsByDay as $shifts)
                    @php $day = $shifts->first()->date; @endphp

                    <section class="space-y-3 print-keep">
                        <h2 class="flex items-center gap-3 text-lg font-extrabold tracking-tight text-zinc-900 sm:text-xl">
                            <span class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-white shadow-card tabular-grid">
                                <span class="text-[0.625rem] font-bold uppercase leading-none text-primary">{{ $day->translatedFormat('D') }}</span>
                                <span class="text-base font-extrabold leading-tight text-zinc-900">{{ $day->format('j') }}</span>
                            </span>
                            {{ ucfirst($day->translatedFormat('l j F Y')) }}
                        </h2>

                        @foreach ($shifts as $shift)
                            <x-ui.card class="print-keep" padding="p-4 sm:p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary tabular-grid">{{ $shift->timeSlot->label() }}</p>
                                        <p class="mt-1 text-base font-bold text-zinc-900">{{ $shift->mission->name }}</p>
                                    </div>

                                    @if ($shift->pivot->assigned_by_admin)
                                        <x-ui.badge tone="plum">Attribué par l'équipe organisatrice</x-ui.badge>
                                    @endif
                                </div>

                                @if (filled($shift->mission->instructions))
                                    <p class="mt-3 rounded-xl bg-zinc-50 p-3 text-sm text-zinc-900">
                                        {{ $shift->mission->instructions }}
                                    </p>
                                @endif
                            </x-ui.card>
                        @endforeach
                    </section>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
