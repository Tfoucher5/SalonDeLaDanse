<x-admin-layout title="Exports">
    <x-slot name="header">
        <x-ui.page-header
            title="Exports"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Trois feuilles, deux formats. Les critères ci-dessous s\'appliquent au fichier téléchargé.' : 'Aucune édition n\'est ouverte pour le moment.'" />
    </x-slot>

    @if ($edition === null)
        <x-ui.empty title="Aucune édition active">
            Les exports s'ouvriront ici dès qu'une édition du Salon sera ouverte.
        </x-ui.empty>
    @else
        <x-ui.card title="Filtrer l'export"
                   subtitle="Sans critère, chaque feuille sort complète.">
            <x-admin.volunteer-filters
                :action="route('admin.exports.index')"
                :criteria="$criteria"
                :missions="$missions"
                :days="$days"
                target="resultats-exports"
                submit="Appliquer les critères">
                <x-ui.button :href="route('admin.volunteers.index', array_filter($criteria))" variant="ghost">
                    Voir ces bénévoles à l'écran
                </x-ui.button>
            </x-admin.volunteer-filters>
        </x-ui.card>

        {{-- Les liens de telechargement portent les criteres : ils se
             recalculent avec eux. --}}
        <div id="resultats-exports" class="space-y-6" aria-live="polite">
        @foreach ($datasets as $dataset)
            @php
                // Les criteres qui ne valent pas pour cette feuille sont retires
                // du lien : un export ne doit jamais laisser croire qu'il filtre
                // sur un critere qu'il ignore.
                $applied = array_filter($criteria, fn ($value, $key) => $value !== null && in_array($key, $dataset->filters(), true), ARRAY_FILTER_USE_BOTH);
                $ignored = array_diff(array_keys(array_filter($criteria)), $dataset->filters());
            @endphp

            <x-ui.card :title="$dataset->label()" :subtitle="$dataset->description()">
                <x-slot name="actions">
                    {{-- Six telechargements egalement legitimes : aucun n'est
                         « l'action » de l'ecran, aucun n'est donc primaire. --}}
                    @foreach ($formats as $format)
                        <x-ui.button :href="route('admin.exports.download', ['dataset' => $dataset, 'format' => $format, ...$applied])">
                            {{ $format->label() }}
                        </x-ui.button>
                    @endforeach
                </x-slot>

                @if ($ignored !== [])
                    <x-ui.alert tone="attention">
                        Cette feuille ignore
                        {{ collect($ignored)->map(fn (string $key) => match ($key) {
                            'name' => 'le filtre par nom',
                            'status' => 'le filtre par statut de validation',
                            default => 'le filtre « '.$key.' »',
                        })->join(' et ') }}
                        : elle est centrée sur le créneau, pas sur le bénévole, et doit continuer
                        à montrer les créneaux vides.
                    </x-ui.alert>
                @endif
            </x-ui.card>
        @endforeach

        </div>

        <x-ui.card title="À savoir sur les formats">
            <x-ui.data-list>
                <x-ui.data-row label="Excel (.xlsx)">
                    Un classeur d'un seul onglet, en-tête figé et filtre automatique. À ouvrir tel
                    quel, ou à imprimer pour un responsable de poste.
                </x-ui.data-row>

                <x-ui.data-row label="CSV">
                    UTF-8 avec marque d'ordre des octets, séparateur point-virgule : Excel en
                    français l'ouvre d'un double-clic sans assistant d'importation.
                </x-ui.data-row>

                <x-ui.data-row label="Confidentialité">
                    Ces fichiers portent les coordonnées de tous les bénévoles. Ils sortent de la
                    plateforme et de ses protections : ne les diffusez qu'à l'équipe.
                </x-ui.data-row>
            </x-ui.data-list>
        </x-ui.card>
    @endif
</x-admin-layout>
