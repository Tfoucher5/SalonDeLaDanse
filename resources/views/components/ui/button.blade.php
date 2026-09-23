{{--
    Bouton unique de l'application, lien ou bouton selon `href`.

    Un seul bouton `primary` par ecran : c'est ce qui rend l'action principale
    evidente. Tout le reste est `secondary`, `ghost` ou `danger`.
--}}

@props([
    'variant' => 'secondary',
    'href' => null,
    'type' => 'submit',
    'size' => 'md',
    'block' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-md px-4 text-sm font-medium transition duration-150 ease-in-out disabled:pointer-events-none disabled:opacity-50';

    $variants = [
        'primary' => 'border border-transparent bg-primary text-white hover:bg-primary-hover',
        'secondary' => 'border border-zinc-200 bg-white text-zinc-900 hover:bg-zinc-100',
        'ghost' => 'border border-transparent bg-transparent text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900',
        'danger' => 'border border-zinc-200 bg-white text-danger hover:bg-zinc-100',
    ];

    // `touch` respecte la cible tactile de 44 px du mobile first ; `md` suffit
    // aux barres d'outils denses de l'administration.
    $sizes = [
        'md' => 'h-10',
        'touch' => 'h-11 min-h-touch',
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
