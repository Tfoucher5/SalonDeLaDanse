<x-app-layout title="Mon planning">
    {{--
        Le planning du benevole, lisible au premier coup d'oeil : pas de grand
        en-tete, une ligne de titre compacte, puis directement les journees.
        C'est aussi la page qu'on imprime et qu'on emporte le jour J.
    --}}
    <div class="flex items-center justify-between gap-3">
        <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl">Mon planning</h1>
            <x-ui.badge :tone="$state->tone()" dot>{{ $state->label() }}</x-ui.badge>
        </div>

        <x-ui.button type="button" class="shrink-0 px-3 sm:px-5 print-hidden" onclick="window.print()" aria-label="Imprimer mon planning">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M7 9V3h10v6M7 17H5a1 1 0 01-1-1v-6a1 1 0 011-1h14a1 1 0 011 1v6a1 1 0 01-1 1h-2M7 14h10v7H7v-7z" />
            </svg>
            <span class="hidden sm:inline">Imprimer</span>
        </x-ui.button>
    </div>

    {{-- L'etat de validation, en une ligne : un benevole qui imprime un
         brouillon doit savoir que sa fiche peut encore changer. --}}
    @if ($shiftsByDay->isNotEmpty())
        @if ($state->isEditable())
            <x-ui.alert tone="attention" class="print-hidden">
                Ce planning n'est pas encore validé par l'équipe organisatrice : il peut encore changer.
            </x-ui.alert>
        @else
            <x-ui.alert tone="success">{{ $state->description() }}</x-ui.alert>
        @endif
    @endif

    <div class="grid items-start gap-5 sm:gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @if ($shiftsByDay->isEmpty())
                <x-ui.empty title="Votre planning est encore vide">
                    Réservez vos créneaux : ils apparaîtront ici, prêts à imprimer.

                    <div class="mt-4 print-hidden">
                        <x-ui.button :href="route('planning.index')" variant="primary" size="touch">
                            Réserver des créneaux
                        </x-ui.button>
                    </div>
                </x-ui.empty>
            @else
                {{-- Une carte par jour, une ligne par creneau : l'horaire a
                     gauche, la mission et ses consignes a droite. --}}
                @foreach ($shiftsByDay as $shifts)
                    @php $day = $shifts->first()->date; @endphp

                    <x-ui.card class="print-keep" padding="p-0">
                        <h2 class="flex items-center gap-3 border-b border-zinc-200 px-4 py-3 text-base font-extrabold tracking-tight text-zinc-900 sm:px-5 sm:text-lg">
                            <span class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-lg bg-primary-soft tabular-grid">
                                <span class="text-[0.5625rem] font-bold uppercase leading-none text-primary">{{ $day->translatedFormat('D') }}</span>
                                <span class="text-sm font-extrabold leading-tight text-zinc-900">{{ $day->format('j') }}</span>
                            </span>
                            {{ ucfirst($day->translatedFormat('l j F Y')) }}
                        </h2>

                        <ol class="divide-y divide-zinc-200">
                            @foreach ($shifts as $shift)
                                <li class="flex gap-4 px-4 py-3.5 print-keep sm:px-5">
                                    <p class="w-24 shrink-0 pt-0.5 text-sm font-bold text-primary tabular-grid">{{ $shift->timeSlot->label() }}</p>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-bold text-zinc-900">{{ $shift->mission->name }}</p>

                                            @if ($shift->pivot->assigned_by_admin)
                                                <x-ui.badge tone="plum">Attribué par l'équipe organisatrice</x-ui.badge>
                                            @endif
                                        </div>

                                        @if (filled($shift->mission->instructions))
                                            <p class="mt-1 text-sm text-zinc-600">{{ $shift->mission->instructions }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </x-ui.card>
                @endforeach
            @endif
        </div>

        {{-- L'identite du benevole : la fiche circule sur papier, elle doit dire
             de qui elle parle sans la barre de navigation. --}}
        <x-ui.card class="print-keep lg:sticky lg:top-24">
            <div class="flex items-center gap-3">
                <x-ui.avatar :user="$user" size="h-12 w-12 text-sm" />

                <div class="min-w-0">
                    <p class="font-bold tracking-tight text-zinc-900">{{ $user->full_name }}</p>
                    <p class="truncate text-sm text-zinc-500">{{ $user->email }}</p>

                    @if ($user->phone !== '')
                        <p class="text-sm text-zinc-500 tabular-grid">{{ $user->phone }}</p>
                    @endif
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-3">
                <x-ui.readonly-field label="Créneaux">{{ $shiftsByDay->flatten(1)->count() }}</x-ui.readonly-field>
                <x-ui.readonly-field label="Jours">{{ $shiftsByDay->count() }}</x-ui.readonly-field>
            </dl>

            @if ($state->isEditable())
                <x-slot name="footer">
                    <a href="{{ route('planning.index') }}" class="inline-flex min-h-touch items-center gap-1.5 text-sm font-bold text-primary hover:text-primary-hover print-hidden">
                        Modifier mes créneaux
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </x-slot>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
