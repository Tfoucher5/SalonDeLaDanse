@use('App\Enums\GaugeLevel')

{{--
    Carte de creneau : le composant le plus manipule de la plateforme.

    Un etat se lit en niveaux de gris — les places restantes sont ecrites en
    toutes lettres, la couleur ne fait que confirmer. Quand une regle empeche la
    reservation, le motif est affiche en clair : « indisponible » n'apprend rien
    au benevole.

    Alpine ne sert qu'au retour visuel pendant l'aller-retour serveur : le bouton
    se desactive et s'annonce, l'etat reel revient avec la page rechargee.
--}}

@props([
    'shift',
    'booked' => false,
    'motive' => null,
    'editable' => false,
])

@php
    $gauge = GaugeLevel::for($shift);
    $isBlocked = ! $booked && $motive !== null;

    $surface = match (true) {
        $booked => 'border-primary bg-primary-soft',
        $isBlocked => 'border-zinc-200 bg-zinc-50',
        default => 'border-zinc-200 bg-white',
    };
@endphp

<article {{ $attributes->merge(['class' => 'flex flex-col rounded-lg border p-4 tabular-grid '.$surface]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="font-medium {{ $isBlocked ? 'text-zinc-400' : 'text-zinc-900' }}">
                {{ $shift->mission->name }}
            </h3>
            <p class="mt-0.5 text-sm text-zinc-500">{{ $shift->timeSlot->label() }}</p>
        </div>

        @if ($booked)
            <x-ui.badge tone="primary-outline" class="shrink-0">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" />
                </svg>
                Réservé
            </x-ui.badge>
        @endif
    </div>

    <x-ui.gauge
        class="mt-3"
        :level="$gauge->value"
        :label="$gauge->label($shift->remaining_places)"
        :remaining="$shift->remaining_places"
        :capacity="$shift->capacity" />

    @if ($isBlocked)
        <p class="mt-2 text-sm text-zinc-500">{{ $motive }}</p>
    @endif

    @if ($editable && $booked)
        <form method="POST"
              action="{{ route('planning.shifts.destroy', $shift) }}"
              x-data="{ pending: false }"
              @submit="pending = true"
              class="mt-4">
            @csrf
            @method('DELETE')

            <x-ui.button variant="danger" size="touch" block x-bind:disabled="pending">
                <span x-show="! pending">Retirer ce créneau</span>
                <span x-show="pending" x-cloak>Enregistrement…</span>
            </x-ui.button>
        </form>
    @elseif ($editable && ! $isBlocked)
        <form method="POST"
              action="{{ route('planning.shifts.store', $shift) }}"
              x-data="{ pending: false }"
              @submit="pending = true"
              class="mt-4">
            @csrf

            <x-ui.button size="touch" block x-bind:disabled="pending">
                <span x-show="! pending">Réserver</span>
                <span x-show="pending" x-cloak>Enregistrement…</span>
            </x-ui.button>
        </form>
    @endif
</article>
