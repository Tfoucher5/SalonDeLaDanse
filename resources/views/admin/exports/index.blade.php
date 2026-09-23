@use('App\Enums\ExportDataset')

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
                   kicker="Sans critère, chaque feuille sort complète">
            <x-slot name="icon">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 5h16l-6 7v6l-4 2v-8L4 5z" />
                </svg>
            </x-slot>

            <x-admin.volunteer-filters
                :action="route('admin.exports.index')"
                :criteria="$criteria"
                :missions="$missions"
                :days="$days"
                target="resultats-exports"
                submit="Appliquer les critères" />
        </x-ui.card>

        {{-- Les liens de telechargement portent les criteres : ils se
             recalculent avec eux, comme le lien vers la liste a l'ecran. --}}
        <div id="resultats-exports" class="space-y-4" aria-live="polite">
        <div class="flex justify-end">
            <x-ui.button :href="route('admin.volunteers.index', array_filter($criteria))" variant="ghost">
                Voir ces bénévoles à l'écran
            </x-ui.button>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
        @foreach ($datasets as $dataset)
            @php
                // Les criteres qui ne valent pas pour cette feuille sont retires
                // du lien : un export ne doit jamais laisser croire qu'il filtre
                // sur un critere qu'il ignore.
                $applied = array_filter($criteria, fn ($value, $key) => $value !== null && in_array($key, $dataset->filters(), true), ARRAY_FILTER_USE_BOTH);
                $ignored = array_diff(array_keys(array_filter($criteria)), $dataset->filters());
            @endphp

            <x-ui.card :title="$dataset->label()" :subtitle="$dataset->description()" class="flex flex-col gap-5 [&>div:last-child]:mt-auto">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        @switch($dataset)
                            @case(ExportDataset::Planning)
                                <path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                                @break
                            @case(ExportDataset::Missions)
                                <path d="M9 5h6M9 3h6a1 1 0 011 1v1h2a1 1 0 011 1v14a1 1 0 01-1 1H6a1 1 0 01-1-1V6a1 1 0 011-1h2V4a1 1 0 011-1zm0 10l2 2 4-4" />
                                @break
                            @default
                                <path d="M9 11a4 4 0 100-8 4 4 0 000 8zm-6 10a6 6 0 0112 0m3-10h4m-4 4h4" />
                        @endswitch
                    </svg>
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

                <x-slot name="footer">
                    {{-- Six telechargements egalement legitimes : aucun n'est
                         « l'action » de l'ecran, aucun n'est donc primaire. --}}
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($formats as $format)
                            <x-ui.button :href="route('admin.exports.download', ['dataset' => $dataset, 'format' => $format, ...$applied])" block>
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v7.6l2.3-2.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L9 11.6V4a1 1 0 011-1zM4 15a1 1 0 011 1v1h10v-1a1 1 0 112 0v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $format->label() }}
                            </x-ui.button>
                        @endforeach
                    </div>
                </x-slot>
            </x-ui.card>
        @endforeach
        </div>

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
