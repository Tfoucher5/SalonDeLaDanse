{{--
    Bouton unique de l'application, lien ou bouton selon `href`.

    Un seul bouton `primary` par ecran : c'est ce qui rend l'action principale
    evidente. Tout le reste est `secondary`, `ghost`, `ink` ou `danger`.
--}}

@props([
    'variant' => 'secondary',
    'href' => null,
    'type' => 'submit',
    'size' => 'md',
    'block' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold transition duration-150 ease-out active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50';

    $variants = [
        'primary' => 'border border-transparent bg-primary text-white shadow-cta hover:bg-primary-hover',
        'secondary' => 'border border-zinc-900/10 bg-white text-zinc-900 hover:bg-zinc-100',
        'ghost' => 'border border-transparent bg-transparent text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900',
        'ink' => 'border border-transparent bg-zinc-900 text-white hover:bg-zinc-800',
        'danger' => 'border border-zinc-900/10 bg-white text-danger hover:bg-zinc-100',
        // Retrait d'un creneau deja retenu : il reprend l'emeraude du badge
        // « Vous participez » de la carte, sans concurrencer le primaire.
        'booked' => 'border border-gauge-free bg-white text-gauge-free hover:bg-gauge-free/5',
    ];

    // `touch` respecte la cible tactile de 44 px du mobile first ; `md` suffit
    // aux barres d'outils denses de l'administration.
    $sizes = [
        'md' => 'h-10',
        'touch' => 'h-12 min-h-touch',
    ];

    $classes = implode(' ', array_filter([
        $base,
        $variants[$variant] ?? $variants['secondary'],
        $sizes[$size] ?? $sizes['md'],
        $block ? 'w-full' : null,
    ]));
@endphp

@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => $type, 'class' => $classes]) }}>{{ $slot }}</button>
@endif
