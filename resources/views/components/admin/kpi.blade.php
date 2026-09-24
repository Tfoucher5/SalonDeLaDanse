{{--
    Carte d'indicateur de la vue d'ensemble (maquette « Vue d'ensemble Admin ») :
    sur-titre et pastille d'icone, grand chiffre suivi de son unite, puis une
    barre de progression ou une ligne de detail teintee.

    `rate` (0 a 100) branche la carte sur l'echelle continue de pourvoi : plus
    l'objectif est atteint, plus chiffre, pastille et barre tirent vers le vert.
    Sans `rate`, `tone` fixe la couleur (`neutral`, `danger`, `free`).

    `href` en fait une carte cliquable : un compteur qui intrigue doit mener a
    la liste qu'il resume.
--}}

@props([
    'label',
    'value',
    'unit' => null,
    'rate' => null,
    'bar' => false,
    'tone' => 'neutral',
    'hint' => null,
    'detailLabel' => null,
    'detailValue' => null,
    'href' => null,
])

@php
    $rate = $rate === null ? null : max(0, min(100, (int) $rate));

    [$valueClass, $chipClass] = match (true) {
        $rate !== null => ['text-staffing', 'bg-staffing-soft text-staffing'],
        $tone === 'danger' => ['text-danger', 'bg-primary-soft text-danger'],
        $tone === 'free' => ['text-gauge-free', 'bg-gauge-free/10 text-gauge-free'],
        default => ['text-zinc-900', 'bg-zinc-100 text-zinc-500'],
    };

    $classes = 'flex flex-col rounded-2xl bg-white p-4 shadow-card ring-1 ring-zinc-900/5 tabular-grid sm:p-5'
        .($rate !== null ? ' staffing-scale' : '')
        .($href !== null ? ' group transition hover:-translate-y-0.5 hover:shadow-lift' : '');

    $tag = $href !== null ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href !== null) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if ($rate !== null) style="--fill: {{ $rate }}" @endif>
    <div class="flex items-start justify-between gap-3">
        <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">{{ $label }}</p>

        @isset($icon)
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $chipClass }}" aria-hidden="true">
                {{ $icon }}
            </span>
        @endisset
    </div>

    <p class="mt-3 flex flex-wrap items-baseline gap-x-2">
        <span class="text-3xl font-extrabold leading-none tracking-tight {{ $valueClass }}">{{ $value }}</span>

        @if ($unit !== null)
            <span class="text-sm text-zinc-500">{{ $unit }}</span>
        @endif
    </p>

    <div class="mt-auto pt-3">
        @if ($bar && $rate !== null)
            <div class="h-2 overflow-hidden rounded-full bg-zinc-200/70" aria-hidden="true">
                <div class="h-full rounded-full bg-staffing" style="width: {{ $rate }}%"></div>
            </div>
        @endif

        @if ($hint !== null)
            <p @class(['text-sm text-zinc-500', 'mt-1.5 text-right' => $bar])>{{ $hint }}</p>
        @endif

        @if ($detailLabel !== null)
            <p class="flex items-center justify-between gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm ring-1 ring-zinc-900/5">
                <span class="text-zinc-500">{{ $detailLabel }}</span>
                <span class="font-bold {{ $valueClass }}">{{ $detailValue }}</span>
            </p>
        @endif
    </div>
</{{ $tag }}>
