@props(['active' => false])

@php
    $classes = $active
        ? 'inline-flex h-9 items-center rounded-full bg-primary-soft px-4 text-sm font-bold text-primary'
        : 'inline-flex h-9 items-center rounded-full px-4 text-sm font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>
