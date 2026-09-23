{{--
    Un chiffre et ce qu'il compte. Utilise sur le dashboard et en back-office.

    `href` en fait une tuile cliquable : un compteur qui intrigue doit mener
    a la liste qu'il resume (les plannings en attente, par exemple).

    `target` ajoute une barre de progression vers un objectif (des comptes
    crees sur les benevoles attendus, par exemple), coloree selon
    `StaffingLevel` : rouge loin du but, vert une fois atteint.
--}}

@use('App\Enums\StaffingLevel')

@props([
    'label',
    'value',
    'hint' => null,
    'href' => null,
    'target' => null,
    'level' => null,
])

@php
    $classes = 'block rounded-xl bg-zinc-100/70 p-4 tabular-grid'
        .($href !== null ? ' group transition hover:bg-white hover:shadow-card hover:ring-1 hover:ring-zinc-900/5' : '');

    // `level` impose la couleur du chiffre sans barre : un compteur de choses
    // restant a faire (plannings non valides) est rouge tant qu'il n'est pas nul.
    $level ??= $target !== null ? StaffingLevel::fromCounts((int) $value, (int) $target) : null;
    $percent = $target > 0 ? (int) round(min((int) $value, (int) $target) / $target * 100) : 0;
@endphp

@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
@endif
    <p class="flex items-center justify-between gap-2 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">
        {{ $label }}

        @if ($href !== null)
            <svg class="h-4 w-4 text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-primary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
            </svg>
        @endif
    </p>
    <p @class(['mt-1 text-base font-bold', $level?->textClass() ?? 'text-zinc-900'])>{{ $value }}</p>

    @if ($target !== null)
        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-zinc-200" aria-hidden="true">
            <div class="h-full rounded-full {{ $level->barClass() }}" style="width: {{ $percent }}%"></div>
        </div>
    @endif

    @if ($hint !== null)
        <p class="mt-1 text-sm text-zinc-500">{{ $hint }}</p>
    @endif
@if ($href !== null)
    </a>
@else
    </div>
@endif
