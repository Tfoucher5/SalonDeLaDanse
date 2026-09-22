@use('App\Enums\GaugeLevel')

{{--
    Carte de créneau : le composant le plus manipulé de la plateforme.

    Un état se lit en niveaux de gris — les places restantes sont écrites en
    toutes lettres, la couleur ne fait que confirmer. Quand une règle empêche la
    réservation, le motif est affiché en clair : « indisponible » n'apprend rien
    au bénévole.

    Alpine ne sert qu'au retour visuel pendant l'aller-retour serveur : le bouton
    se désactive et s'annonce, l'état réel revient avec la page rechargée.
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

    $title = $isBlocked ? 'text-zinc-400' : 'text-zinc-900';

    $gaugeColor = match ($gauge) {
        GaugeLevel::Free => 'text-gauge-free',
        GaugeLevel::Tight => 'text-gauge-tight',
        GaugeLevel::Full => 'text-gauge-full',
    };
@endphp

<article {{ $attributes->merge(['class' => 'tabular-grid rounded-lg border p-4 '.$surface]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h4 class="font-medium {{ $title }}">{{ $shift->mission->name }}</h4>
            <p class="mt-0.5 text-sm text-zinc-500">{{ $shift->timeSlot->label() }}</p>
        </div>

        @if ($booked)
            <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-primary bg-white px-2 py-1 text-sm font-medium text-primary">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" />
                </svg>
                Réservé
            </span>
        @endif
    </div>

    <p class="mt-3 text-sm font-medium {{ $gaugeColor }}">
        {{ $gauge->label($shift->remaining_places) }}
    </p>

    @if ($isBlocked)
        <p class="mt-2 text-sm text-zinc-500">{{ $motive }}</p>
    @endif

    @if ($editable && $booked)
        <form method="POST"
              action="{{ route('planning.shifts.destroy', $shift) }}"
              x-data="{ pending: false }"
              @submit="pending = true"
              class="mt-3">
            @csrf
            @method('DELETE')

            <button type="submit"
                    x-bind:disabled="pending"
                    class="flex h-11 w-full items-center justify-center rounded-md border border-zinc-200 bg-white px-4 font-medium text-danger hover:bg-zinc-100 disabled:opacity-50">
                <span x-show="! pending">Retirer ce créneau</span>
                <span x-show="pending" x-cloak>Enregistrement…</span>
            </button>
        </form>
    @elseif ($editable && ! $isBlocked)
        <form method="POST"
              action="{{ route('planning.shifts.store', $shift) }}"
              x-data="{ pending: false }"
              @submit="pending = true"
              class="mt-3">
            @csrf

            <button type="submit"
                    x-bind:disabled="pending"
                    class="flex h-11 w-full items-center justify-center rounded-md border border-zinc-200 bg-white px-4 font-medium text-zinc-900 hover:bg-zinc-100 disabled:opacity-50">
                <span x-show="! pending">Réserver</span>
                <span x-show="pending" x-cloak>Enregistrement…</span>
            </button>
        </form>
    @endif
</article>
