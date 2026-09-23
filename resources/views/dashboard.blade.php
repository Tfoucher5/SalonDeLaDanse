<x-app-layout title="Tableau de bord">
    <x-slot name="header">
        <x-ui.page-header
            :title="'Bonjour '.$user->first_name"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? null : 'Aucune édition n\'est ouverte pour le moment.'">
            <x-slot name="actions">
                <x-ui.button :href="route('planning.summary')" size="touch">
                    Ma fiche
                </x-ui.button>

                <x-ui.button :href="route('planning.index')" variant="primary" size="touch">
                    Voir le planning
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    {{-- L'etat du planning : la premiere chose a savoir en arrivant. --}}
    <x-ui.card title="Mon planning">
        <x-slot name="actions">
            <x-ui.badge :tone="$state->tone()">{{ $state->label() }}</x-ui.badge>
        </x-slot>

        <p class="text-zinc-900">{{ $state->description() }}</p>

        @unless ($state->isEditable())
            <x-ui.alert class="mt-4">
                Le planning est en <strong class="font-medium text-zinc-900">consultation seule</strong> :
                vous pouvez le lire, mais aucune modification n'est possible.
            </x-ui.alert>
        @endunless
    </x-ui.card>

    @if ($edition)
        <div class="grid gap-6 md:grid-cols-2">
            <x-ui.card title="Dates du Salon">
                <x-ui.data-list>
                    @foreach ($edition->days() as $day)
                        <x-ui.data-row :label="ucfirst($day->translatedFormat('l'))">
                            {{ $day->translatedFormat('j F Y') }}
                        </x-ui.data-row>
                    @endforeach
                </x-ui.data-list>
            </x-ui.card>

            <x-ui.card title="Inscriptions au planning">
                <x-ui.data-list>
                    <x-ui.data-row label="Ouverture">
                        {{ $edition->registration_opens_at?->translatedFormat('j F Y à H\hi') ?? 'Déjà ouvertes' }}
                    </x-ui.data-row>

                    <x-ui.data-row label="Fermeture">
                        {{ $edition->registration_closes_at?->translatedFormat('j F Y à H\hi') ?? 'Pas de date limite' }}
                    </x-ui.data-row>

                    <x-ui.data-row label="Créneaux par bénévole">
                        de {{ $edition->min_slots_per_volunteer }} à {{ $edition->max_slots_per_volunteer }}
                    </x-ui.data-row>
                </x-ui.data-list>
            </x-ui.card>
        </div>

        <x-ui.card title="Vos engagements" subtitle="Ces règles s'appliquent à tous les bénévoles du Salon.">
            <ul class="space-y-3 text-sm text-zinc-900">
                @foreach ($commitmentRules as $rule)
                    <li class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm4 5.7l-5 5a1 1 0 01-1.4 0l-2.3-2.3a1 1 0 011.4-1.4l1.6 1.6 4.3-4.3a1 1 0 011.4 1.4z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $rule }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    <x-ui.card title="Une question ?"
               :subtitle="$contact ? 'L\'équipe organisatrice vous répond.' : null">
        @if ($contact)
            <x-ui.data-list>
                @isset($contact['name'])
                    <x-ui.data-row label="Contact">{{ $contact['name'] }}</x-ui.data-row>
                @endisset

                @isset($contact['email'])
                    <x-ui.data-row label="E-mail">
                        <a class="text-primary hover:text-primary-hover" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                    </x-ui.data-row>
                @endisset

                @isset($contact['phone'])
                    <x-ui.data-row label="Téléphone">
                        <a class="text-primary hover:text-primary-hover" href="tel:{{ preg_replace('/\s+/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>
                    </x-ui.data-row>
                @endisset
            </x-ui.data-list>
        @else
            <p class="text-sm text-zinc-500">
                Les coordonnées de l'équipe organisatrice ne sont pas encore renseignées.
            </p>
        @endif
    </x-ui.card>
</x-app-layout>
