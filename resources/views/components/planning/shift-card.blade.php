@use('App\Enums\GaugeLevel')

{{--
    Carte de creneau, en tuile a jauge (maquette Stitch « Tuiles avec Jauges ») :
    la disponibilite en tete, une jauge a segments, la mission, puis l'action
    sur toute la largeur.

    Un etat se lit en niveaux de gris — les places restantes sont ecrites en
    toutes lettres, la couleur ne fait que confirmer. Chaque segment de la
    jauge est une place encore libre. Quand une regle empeche la reservation,
    le motif prend la place du bouton, en clair dans une tuile grise :
    « indisponible » n'apprend rien.

    Les formulaires `data-planning-action` sont envoyes sans recharger la page
    par resources/js/planning.js, qui remplace ensuite la carte par sa version
    a jour. Sans JavaScript, ils s'envoient normalement, et `focused` ramene
    alors la page sur le creneau de la derniere action.

    Pendant l'aller-retour serveur, le bouton se desactive et s'annonce.
--}}

@props([
    'shift',
    'booked' => false,
    'motive' => null,
    'editable' => false,
    'focused' => false,
])

@php
    $gauge = GaugeLevel::for($shift);
    $remaining = max(0, $shift->remaining_places);
    $capacity = max(0, $shift->capacity);
    $isBlocked = ! $booked && $motive !== null;

    // Au-dela de huit places, des segments deviendraient illisibles sur un
    // telephone : la jauge passe alors en barre continue.
    $segmented = $capacity > 0 && $capacity <= 8;
    $percent = $capacity > 0 ? (int) round($remaining / $capacity * 100) : 0;

    $segmentColor = match ($gauge) {
        GaugeLevel::Free => 'bg-gauge-free',
        GaugeLevel::Tight => 'bg-gauge-tight',
        GaugeLevel::Full => 'bg-zinc-200',
    };

    $surface = match (true) {
        $booked => 'bg-white shadow-card ring-2 ring-gauge-free/50',
        $isBlocked => 'bg-zinc-50 ring-1 ring-zinc-900/5',
        default => 'bg-white shadow-card ring-1 ring-zinc-900/5 hover:-translate-y-0.5 hover:shadow-lift',
    };
@endphp

<article id="creneau-{{ $shift->id }}"
         @if ($focused) x-data x-init="$el.scrollIntoView({ block: 'center', behavior: 'instant' })" @endif
         {{ $attributes->merge(['class' => 'flex min-h-[11rem] flex-col justify-between rounded-2xl p-5 transition duration-200 tabular-grid '.$surface]) }}>
    <div>
        <div class="mb-2 flex items-center justify-between gap-2">
            <span class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-400">Disponibilité</span>

            @if ($booked)
                <x-ui.badge tone="free" class="shrink-0">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" />
                    </svg>
                    Vous participez
                </x-ui.badge>
            @else
                <x-ui.badge :tone="$gauge->value" :dot="$gauge === GaugeLevel::Tight" class="shrink-0">
                    {{ $gauge === GaugeLevel::Tight && $remaining === 1 ? 'Dernière place !' : $gauge->label($remaining) }}
                </x-ui.badge>
            @endif
        </div>

        <div class="flex items-center gap-1.5" role="img"
             aria-label="{{ $gauge->label($remaining) }} sur {{ $capacity }}">
            @if ($segmented)
                @for ($segment = 1; $segment <= $capacity; $segment++)
                    <span class="h-1.5 flex-1 rounded-full {{ $segment <= $remaining ? $segmentColor : 'bg-zinc-200' }}"></span>
                @endfor
            @else
                <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-200">
                    <span class="block h-full rounded-full {{ $segmentColor }}" style="width: {{ $percent }}%"></span>
                </span>
            @endif
        </div>

        <h3 class="py-3 text-lg font-bold leading-snug tracking-tight {{ $isBlocked ? 'text-zinc-500' : 'text-zinc-900' }}">
            {{ $shift->mission->name }}
        </h3>

        @if ($shift->mission->instructions && ! $isBlocked)
            <p class="-mt-1 mb-3 line-clamp-2 text-sm text-zinc-500">{{ $shift->mission->instructions }}</p>
        @endif
    </div>

    @if ($isBlocked)
        <p class="flex min-h-touch items-center justify-center rounded-xl bg-zinc-100 px-4 py-2.5 text-center text-sm font-semibold text-zinc-500">
            {{ $motive }}
        </p>
    @elseif ($editable)
        <div class="pt-2">
            @if ($booked)
                <form method="POST"
                      data-planning-action
                      action="{{ route('planning.shifts.destroy', $shift) }}"
                      x-data="{ pending: false }"
                      @submit="pending = true">
                    @csrf
                    @method('DELETE')

                    <x-ui.button variant="booked" size="touch" block x-bind:disabled="pending">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" x-show="! pending">
                            <path fill-rule="evenodd" d="M4.3 4.3a1 1 0 011.4 0L10 8.6l4.3-4.3a1 1 0 111.4 1.4L11.4 10l4.3 4.3a1 1 0 01-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 01-1.4-1.4L8.6 10 4.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                        </svg>
                        <span x-show="! pending">Se désister</span>
                        <span x-show="pending" x-cloak>Enregistrement…</span>
                    </x-ui.button>
                </form>
            @else
                <form method="POST"
                      data-planning-action
                      action="{{ route('planning.shifts.store', $shift) }}"
                      x-data="{ pending: false }"
                      @submit="pending = true">
                    @csrf

                    <x-ui.button variant="primary" size="touch" block x-bind:disabled="pending">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" x-show="! pending">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        <span x-show="! pending">Réserver ce créneau</span>
                        <span x-show="pending" x-cloak>Enregistrement…</span>
                    </x-ui.button>
                </form>
            @endif
        </div>
    @endif
</article>
