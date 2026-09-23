{{--
    Jauge de remplissage d'un creneau.

    Les places restantes sont ecrites en toutes lettres : la barre et la couleur
    ne font que confirmer. L'information reste entiere en niveaux de gris, et
    « complet » est gris, jamais rouge — un creneau plein n'est pas une erreur.
--}}

@props([
    'level' => 'free',
    'label',
    'remaining' => 0,
    'capacity' => 0,
])

@php
    $colors = [
        'free' => ['text' => 'text-gauge-free', 'bar' => 'bg-gauge-free'],
        'tight' => ['text' => 'text-gauge-tight', 'bar' => 'bg-gauge-tight'],
        'full' => ['text' => 'text-gauge-full', 'bar' => 'bg-gauge-full'],
    ];

    $color = $colors[$level] ?? $colors['free'];
    $taken = max(0, $capacity - max(0, $remaining));
    $percent = $capacity > 0 ? (int) round($taken / $capacity * 100) : 100;
@endphp

<div {{ $attributes->merge(['class' => 'tabular-grid']) }}>
    <p class="text-sm font-medium {{ $color['text'] }}">{{ $label }}</p>

    <div class="mt-1.5 h-1.5 overflow-hidden rounded-md bg-zinc-200"
         role="img"
         aria-label="{{ $taken }} place{{ $taken > 1 ? 's' : '' }} prise{{ $taken > 1 ? 's' : '' }} sur {{ $capacity }}">
        <div class="h-full rounded-md {{ $color['bar'] }}" style="width: {{ $percent }}%"></div>
    </div>
</div>
