@use('App\Enums\GaugeLevel')

{{--
    Carte de créneau : le composant le plus manipulé de la plateforme.

    Un état se lit en niveaux de gris — les places restantes sont écrites en
    toutes lettres, la couleur ne fait que confirmer. Quand une règle empêche la
    réservation, le motif est affiché en clair : « indisponible » n'apprend rien
    au bénévole.
--}}

@props([
    'shift',
    'booked' => false,
    'reason' => null,
])

@php
    $gauge = GaugeLevel::for($shift);
    $isFull = $gauge === GaugeLevel::Full;

    $motive = $booked ? null : ($isFull ? 'Toutes les places de ce créneau sont prises.' : $reason);
    $isBlocked = ! $booked && ($isFull || $reason !== null);

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

    @if ($motive)
        <p class="mt-2 text-sm text-zinc-500">{{ $motive }}</p>
    @endif
</article>
