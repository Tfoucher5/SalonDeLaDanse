{{--
    Jauge a segments de la maquette « Tuiles avec Jauges » : un segment par
    place libre, dans la couleur du niveau. Au-dela de huit places, des
    segments deviendraient illisibles sur un telephone : la jauge passe alors
    en barre continue.

    `label` est lu par les lecteurs d'ecran : les segments seuls ne disent rien.
--}}

@props([
    'remaining' => 0,
    'capacity' => 0,
    'level' => 'free',
    'label' => null,
])

@php
    $remaining = max(0, (int) $remaining);
    $capacity = max(0, (int) $capacity);
    $segmented = $capacity > 0 && $capacity <= 8;
    $percent = $capacity > 0 ? (int) round(min($remaining, $capacity) / $capacity * 100) : 0;

    $color = match ($level) {
        'tight' => 'bg-gauge-tight',
        'full' => 'bg-zinc-200',
        // Poste a pourvoir, lu par l'equipe organisatrice (StaffingLevel).
        'danger' => 'bg-danger/70',
        default => 'bg-gauge-free',
    };
@endphp

<div role="img" aria-label="{{ $label ?? $remaining.' place'.($remaining > 1 ? 's' : '').' libre'.($remaining > 1 ? 's' : '').' sur '.$capacity }}"
     {{ $attributes->merge(['class' => 'flex items-center gap-1.5']) }}>
    @if ($segmented)
        @for ($segment = 1; $segment <= $capacity; $segment++)
            <span class="h-1.5 flex-1 rounded-full {{ $segment <= $remaining ? $color : 'bg-zinc-200' }}"></span>
        @endfor
    @else
        <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-200">
            <span class="block h-full rounded-full {{ $color }}" style="width: {{ $percent }}%"></span>
        </span>
    @endif
</div>
