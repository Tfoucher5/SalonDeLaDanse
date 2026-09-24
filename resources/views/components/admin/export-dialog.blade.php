{{--
    Le bouton « Exporter » d'un ecran, et sa fenetre.

    La fenetre exporte ce que l'ecran montre : elle reprend ses criteres en
    champs caches et les rappelle en clair, pour qu'on sache ce que contiendra
    le fichier avant de l'ouvrir. A poser dans la zone rejouee par le filtrage
    sans clic : les criteres suivent ainsi la saisie, sans rechargement.

    `datasets` liste les feuilles qui ont un sens pour l'ecran, la premiere
    etant celle qui lui correspond. `day`, s'il est donne, est le jour affiche :
    la fenetre propose alors de s'y limiter ou d'exporter toute l'edition.
--}}

@props([
    'datasets',
    'criteria',
    'missions',
    'day' => null,
])

@php
    $filters = collect($criteria)->filter()->except($day !== null ? ['day'] : []);

    $summary = $filters->map(fn ($value, string $key): string => match ($key) {
        'name' => 'Nom : « '.$value.' »',
        'mission' => 'Mission : '.($missions->firstWhere('id', $value)?->name ?? '?'),
        'status' => $value === 'validated' ? 'Planning validé' : 'Planning non validé',
        'day' => ucfirst(\Illuminate\Support\Carbon::parse($value)->translatedFormat('l j F')),
        default => $value,
    });
@endphp

<div x-data="{ open: false }" {{ $attributes->only('class') }}>
    <x-ui.button type="button" variant="secondary" x-on:click="open = true">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v7.6l2.3-2.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L9 11.6V4a1 1 0 011-1zM4 15a1 1 0 011 1v1h10v-1a1 1 0 112 0v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        Exporter
    </x-ui.button>

    {{-- Teleportee dans <body> : posee dans la barre collante du planning,
         dont le `backdrop-blur` sert de repere aux elements `fixed`, la
         fenetre y resterait enfermee au lieu de couvrir l'ecran. --}}
    <template x-teleport="body">
    <div x-show="open"
         x-cloak
         x-on:keydown.escape.window="open = false"
         class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
         role="dialog"
         aria-modal="true"
         aria-labelledby="export-titre">
        <div x-show="open" x-transition.opacity
             x-on:click="open = false"
             class="absolute inset-0 bg-zinc-900/40"
             aria-hidden="true"></div>

        <div x-show="open" x-transition
             class="relative max-h-full w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 ring-1 ring-zinc-900/5 shadow-overlay sm:p-6">
            <h2 id="export-titre" class="text-lg font-bold tracking-tight text-zinc-900">Exporter cette vue</h2>

            {{-- Le fichier se telecharge : la fenetre se referme d'elle-meme. --}}
            <form method="GET" action="{{ route('admin.exports.download') }}" x-on:submit="open = false" class="mt-4 space-y-5">
                @foreach ($filters as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

                <div>
                    <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">Filtres repris de l'écran</p>

                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse ($summary as $label)
                            <x-ui.badge tone="primary">{{ $label }}</x-ui.badge>
                        @empty
                            <p class="text-sm text-zinc-500">Aucun : toute l'édition est exportée.</p>
                        @endforelse
                    </div>
                </div>

                @if ($day !== null)
                    <fieldset>
                        <legend class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">Période</legend>

                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ([$day->toDateString() => ucfirst($day->translatedFormat('l j F')), '' => 'Tous les jours'] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-zinc-50 p-3 ring-1 ring-zinc-900/5 transition hover:bg-white has-[:checked]:bg-white has-[:checked]:ring-primary/30">
                                    <input type="radio" name="day" value="{{ $value }}" @checked($loop->first)
                                           class="h-5 w-5 border-zinc-200 text-primary focus:ring-2 focus:ring-primary-ring">
                                    <span class="text-sm font-medium text-zinc-900">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <fieldset>
                    <legend class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">Contenu du fichier</legend>

                    <div class="mt-2 space-y-2">
                        @foreach ($datasets as $dataset)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-900/5 transition hover:bg-white has-[:checked]:bg-white has-[:checked]:ring-primary/30">
                                <input type="radio" name="dataset" value="{{ $dataset->value }}" @checked($loop->first)
                                       class="mt-0.5 h-5 w-5 border-zinc-200 text-primary focus:ring-2 focus:ring-primary-ring">

                                <span class="min-w-0">
                                    <span class="block font-medium text-zinc-900">{{ $dataset->label() }}</span>
                                    <span class="block text-sm text-zinc-500">{{ $dataset->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <p class="text-xs text-zinc-500">
                    Le fichier porte les coordonnées des bénévoles : ne le diffusez qu'à l'équipe.
                </p>

                {{-- Le format est porte par le bouton : Excel est l'usage courant,
                     le CSV reste a portee pour un reimport. --}}
                <div class="flex flex-wrap justify-end gap-2">
                    <x-ui.button type="button" variant="ghost" size="touch" x-on:click="open = false">
                        Annuler
                    </x-ui.button>

                    <x-ui.button type="submit" name="format" value="csv" size="touch">
                        CSV
                    </x-ui.button>

                    <x-ui.button type="submit" name="format" value="xlsx" variant="primary" size="touch">
                        Télécharger en Excel
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
    </template>
</div>
