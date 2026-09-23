{{--
    Bloc d'identite : le logotype du Salon et a qui s'adresse l'outil.

    `md` pour la barre de navigation, logotype et accroche cote a cote ;
    `lg` pour les pages d'accueil et d'authentification, empiles et centres.
--}}

@props([
    'href' => null,
    'tagline' => 'Espace bénévoles',
    'size' => 'md',
])

@php
    $tag = $href !== null ? 'a' : 'div';
    $isLarge = $size === 'lg';
@endphp

<{{ $tag }} @if ($href !== null) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'inline-flex rounded-xl '.($isLarge ? 'flex-col items-center gap-3' : 'items-center gap-3')]) }}>
    <x-application-logo class="{{ $isLarge ? 'h-20 sm:h-24' : 'h-9' }}" />

    @if ($tagline !== null)
        @if ($isLarge)
            <span class="rounded-full bg-primary-soft px-3 py-1 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">{{ $tagline }}</span>
        @else
            <span class="hidden border-s border-zinc-200 ps-3 text-[0.6875rem] font-bold uppercase leading-tight tracking-[0.08em] text-primary min-[380px]:block">
                {{ $tagline }}
            </span>
        @endif
    @endif
</{{ $tag }}>
