@use('App\Enums\GaugeLevel')

{{--
    Carte de creneau : le composant le plus manipule de la plateforme.

    Un etat se lit en niveaux de gris — les places restantes sont ecrites en
    toutes lettres, la couleur ne fait que confirmer. Quand une regle empeche la
    reservation, la carte s'efface pour laisser ressortir les creneaux ouverts,
    mais le motif reste affiche en clair : « indisponible » n'apprend rien.

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
    $isBlocked = ! $booked && $motive !== null;

    $surface = match (true) {
        $booked => 'bg-primary-soft/60 ring-2 ring-primary',
        $isBlocked => 'border border-dashed border-zinc-300 opacity-70',
        default => 'bg-white shadow-card ring-1 ring-zinc-900/5 hover:shadow-lift',
    };
@endphp

<article id="creneau-{{ $shift->id }}"
         @if ($focused) x-data x-init="$el.scrollIntoView({ block: 'center', behavior: 'instant' })" @endif
         {{ $attributes->merge(['class' => 'flex flex-col rounded-2xl transition duration-200 tabular-grid '.($isBlocked ? 'p-4 ' : 'p-4 sm:p-5 ').$surface]) }}>
    <div class="flex items-start justify-between gap-3">
        <h3 class="min-w-0 font-bold leading-snug tracking-tight {{ $isBlocked ? 'text-sm text-zinc-500' : 'text-base text-zinc-900' }}">
            {{ $shift->mission->name }}
        </h3>

        @if ($booked)
            <x-ui.badge tone="primary-outline" class="shrink-0">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" />
                </svg>
                Réservé
            </x-ui.badge>
        @elseif ($isBlocked)
            <span class="shrink-0 text-xs font-semibold text-gauge-full">{{ $gauge->label($shift->remaining_places) }}</span>
        @else
            <x-ui.badge :tone="$gauge->value" dot class="shrink-0">
                {{ $gauge->label($shift->remaining_places) }}
            </x-ui.badge>
        @endif
    </div>

    @if ($isBlocked)
        <p class="mt-1 text-xs text-zinc-500">{{ $motive }}</p>
    @else
        @if ($shift->mission->instructions)
            <p class="mt-1.5 line-clamp-2 text-sm text-zinc-500">{{ $shift->mission->instructions }}</p>
        @endif

        @if ($booked)
            <p @class([
                'mt-1.5 text-sm font-semibold',
                'text-gauge-free' => $gauge === GaugeLevel::Free,
                'text-gauge-tight' => $gauge === GaugeLevel::Tight,
                'text-gauge-full' => $gauge === GaugeLevel::Full,
            ])>{{ $gauge->label($shift->remaining_places) }}</p>
        @endif

        @if ($editable)
            <div class="mt-auto flex justify-end pt-4">
                @if ($booked)
                    <form method="POST"
                          data-planning-action
                          action="{{ route('planning.shifts.destroy', $shift) }}"
                          x-data="{ pending: false }"
                          @submit="pending = true">
                        @csrf
                        @method('DELETE')

                        <x-ui.button variant="danger" size="touch" x-bind:disabled="pending">
                            <span x-show="! pending">Retirer ce créneau</span>
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

                        <x-ui.button variant="primary" size="touch" x-bind:disabled="pending">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" x-show="! pending">
                                <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 5a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                            </svg>
                            <span x-show="! pending">Réserver</span>
                            <span x-show="pending" x-cloak>Enregistrement…</span>
                        </x-ui.button>
                    </form>
                @endif
            </div>
        @endif
    @endif
</article>
