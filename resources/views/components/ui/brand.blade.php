{{-- Bloc d'identite : la marque, le nom du Salon, et a qui s'adresse l'outil. --}}

@props([
    'href' => null,
    'tagline' => 'Espace bénévoles',
    'size' => 'md',
])

@php
    $markSize = $size === 'lg' ? 'h-10 w-10' : 'h-8 w-8';
    $nameSize = $size === 'lg' ? 'text-lg' : 'text-base';
@endphp

@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 rounded-md']) }}>
        <x-application-logo class="{{ $markSize }}" />

        <span class="flex min-w-0 flex-col leading-tight">
            <span class="truncate font-semibold text-zinc-900 {{ $nameSize }}">{{ config('app.name') }}</span>
            @if ($tagline !== null)
                <span class="truncate text-xs text-zinc-500">{{ $tagline }}</span>
            @endif
        </span>
    </a>
@else
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
        <x-application-logo class="{{ $markSize }}" />

        <span class="flex min-w-0 flex-col leading-tight">
            <span class="truncate font-semibold text-zinc-900 {{ $nameSize }}">{{ config('app.name') }}</span>
            @if ($tagline !== null)
                <span class="truncate text-xs text-zinc-500">{{ $tagline }}</span>
            @endif
        </span>
    </div>
@endif
