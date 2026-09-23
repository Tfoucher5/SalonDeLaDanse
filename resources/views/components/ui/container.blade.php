{{-- Gouttiere laterale et largeur de lecture, identiques sur toutes les pages. --}}

@props(['size' => 'md'])

@php
    $widths = [
        'sm' => 'max-w-2xl',
        'md' => 'max-w-4xl',
        'lg' => 'max-w-5xl',
        'xl' => 'max-w-7xl',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'mx-auto w-full px-4 sm:px-6 lg:px-8 '.($widths[$size] ?? $widths['md'])]) }}>
    {{ $slot }}
</div>
