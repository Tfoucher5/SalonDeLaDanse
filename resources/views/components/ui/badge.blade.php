{{--
    Etiquette d'etat, en capsule. La couleur confirme, le mot informe : un
    badge sans libelle lisible n'existe pas. `dot` ajoute la pastille d'etat.
--}}

@props([
    'tone' => 'neutral',
    'dot' => false,
])

@php
    $tones = [
        'neutral' => ['surface' => 'bg-zinc-100 text-zinc-600', 'dot' => 'bg-zinc-400'],
        'primary' => ['surface' => 'bg-primary-soft text-primary', 'dot' => 'bg-primary'],
        'primary-outline' => ['surface' => 'bg-white text-primary ring-1 ring-inset ring-primary/40', 'dot' => 'bg-primary'],
        'plum' => ['surface' => 'bg-plum-soft text-plum', 'dot' => 'bg-plum'],
        'free' => ['surface' => 'bg-gauge-free/10 text-gauge-free', 'dot' => 'bg-gauge-free'],
        'tight' => ['surface' => 'bg-gauge-tight/10 text-gauge-tight', 'dot' => 'bg-gauge-tight'],
        'full' => ['surface' => 'bg-zinc-100 text-gauge-full', 'dot' => 'bg-zinc-400'],
        'danger' => ['surface' => 'bg-danger/10 text-danger', 'dot' => 'bg-danger'],
    ];

    $style = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex h-7 items-center gap-1.5 whitespace-nowrap rounded-full px-3 text-xs font-semibold '.$style['surface']]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $style['dot'] }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
