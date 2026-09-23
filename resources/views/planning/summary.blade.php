<x-app-layout title="Ma fiche bénévole" width="md">
    <x-slot name="header">
        <x-ui.page-header
            title="Ma fiche bénévole"
            :eyebrow="$edition?->name"
            subtitle="Vos missions, vos horaires et les consignes qui vont avec.">
            <x-slot name="actions">
                <x-ui.badge :tone="$state->tone()">{{ $state->label() }}</x-ui.badge>

                <x-ui.button type="button" class="print-hidden" onclick="window.print()">
                    Imprimer
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('status'))
        <x-ui.alert tone="primary" class="print-hidden">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- L'identite du benevole : la fiche circule sur papier, elle doit dire
         de qui elle parle sans la barre de navigation. --}}
    <x-ui.card class="print-keep">
        <div class="flex items-center gap-4">
            <x-ui.avatar :user="$user" size="h-12 w-12" />

            <div class="min-w-0">
                <p class="font-medium text-zinc-900">{{ $user->full_name }}</p>
                <p class="truncate text-sm text-zinc-500">{{ $user->email }}</p>

                @if ($user->phone !== '')
                    <p class="text-sm text-zinc-500 tabular-grid">{{ $user->phone }}</p>
                @endif
            </div>
        </div>
    </x-ui.card>

    @if ($shiftsByDay->isEmpty())
        <x-ui.empty title="Votre planning est encore vide">
            Choisissez vos créneaux dans le planning : ils apparaîtront ici, prêts à imprimer.

            <div class="mt-4 print-hidden">
                <x-ui.button :href="route('planning.index')" size="touch">
                    Composer mon planning
                </x-ui.button>
            </div>
        </x-ui.empty>
    @else
        @foreach ($shiftsByDay as $shifts)
            @php $day = $shifts->first()->date; @endphp

            <section class="space-y-3 print-keep">
                <h2 class="border-b border-zinc-200 pb-2 text-lg font-semibold text-zinc-900">
                    {{ ucfirst($day->translatedFormat('l j F Y')) }}
                </h2>

                @foreach ($shifts as $shift)
                    <x-ui.card class="print-keep">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-zinc-900">{{ $shift->mission->name }}</p>
                                <p class="mt-0.5 text-sm text-zinc-500 tabular-grid">{{ $shift->timeSlot->label() }}</p>
                            </div>

                            @if ($shift->pivot->assigned_by_admin)
                                <x-ui.badge>Attribué par l'équipe organisatrice</x-ui.badge>
                            @endif
                        </div>

                        @if (filled($shift->mission->instructions))
                            <p class="mt-3 border-t border-zinc-200 pt-3 text-sm text-zinc-900">
                                {{ $shift->mission->instructions }}
                            </p>
                        @endif
                    </x-ui.card>
                @endforeach
            </section>
        @endforeach

        @unless ($state->isEditable())
            <x-ui.alert>{{ $state->description() }}</x-ui.alert>
        @else
            <x-ui.alert class="print-hidden">
                Ce planning n'est pas encore validé par l'équipe organisatrice : il peut encore changer.
            </x-ui.alert>
        @endunless
    @endif

    <div class="print-hidden">
        <x-ui.button :href="route('planning.index')" variant="ghost">
            Retour au planning
        </x-ui.button>
    </div>
</x-app-layout>
