{{-- Bloc d'identite : la marque, le nom du Salon, et a qui s'adresse l'outil. --}}

@props([
    'href' => null,
    'tagline' => 'Espace bénévoles',
    'size' => 'md',
])

@php
    $markSize = $size === 'lg' ? 'h-12 w-12' : 'h-9 w-9';
    $nameSize = $size === 'lg' ? 'text-lg' : 'text-[0.9375rem]';
    $tag = $href !== null ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href !== null) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 rounded-xl']) }}>
    <x-application-logo class="{{ $markSize }}" />

    <span class="flex min-w-0 flex-col leading-tight">
        <span class="truncate font-bold tracking-tight text-zinc-900 {{ $nameSize }}">{{ config('app.name') }}</span>
        @if ($tagline !== null)
            <span class="truncate text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">{{ $tagline }}</span>
        @endif
    </span>
</{{ $tag }}>
