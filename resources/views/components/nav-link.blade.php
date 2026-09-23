@props(['active' => false])

@php
    $classes = $active
        ? 'inline-flex h-16 items-center border-b-2 border-primary px-1 text-sm font-medium text-zinc-900'
        : 'inline-flex h-16 items-center border-b-2 border-transparent px-1 text-sm font-medium text-zinc-500 transition hover:border-zinc-200 hover:text-zinc-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>
