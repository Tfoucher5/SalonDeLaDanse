{{--
    Jauge de remplissage d'un creneau.

    Les places restantes sont ecrites en toutes lettres : la barre et la couleur
    ne font que confirmer. L'information reste entiere en niveaux de gris, et
    « complet » est gris, jamais rouge — un creneau plein n'est pas une erreur.

    `scale` est la lecture du back-office : la teinte suit le taux de
    remplissage en continu, du rouge au vert (`.staffing-scale`, app.css).
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
        'full' => ['text' => 'text-gauge-full', 'bar' => 'bg-zinc-400'],
        // Poste a pourvoir, lu par l'equipe organisatrice (StaffingLevel).
        'danger' => ['text' => 'text-danger', 'bar' => 'bg-danger/70'],
        'scale' => ['text' => 'text-staffing', 'bar' => 'bg-staffing'],
    ];

    $color = $colors[$level] ?? $colors['free'];
    $taken = max(0, $capacity - max(0, $remaining));
    $percent = $capacity > 0 ? (int) round($taken / $capacity * 100) : 100;
    $isScale = $level === 'scale';
@endphp

<div {{ $attributes->merge(['class' => 'tabular-grid'.($isScale ? ' staffing-scale' : '')]) }}
     @if ($isScale) style="--fill: {{ $percent }}" @endif>
    <p class="text-sm font-semibold {{ $color['text'] }}">{{ $label }}</p>

    <div @class(['mt-1.5 overflow-hidden rounded-full', 'h-2 bg-zinc-200/70' => $isScale, 'h-1.5 bg-zinc-100' => ! $isScale])
         role="img"
         aria-label="{{ $taken }} place{{ $taken > 1 ? 's' : '' }} prise{{ $taken > 1 ? 's' : '' }} sur {{ $capacity }}">
        <div class="h-full rounded-full {{ $color['bar'] }}" style="width: {{ $percent }}%"></div>
    </div>
</div>
