@props(['active' => false])

@php
    $classes = $active
        ? 'flex min-h-touch w-full items-center border-s-4 border-primary bg-primary-soft px-4 py-2 text-base font-medium text-primary'
        : 'flex min-h-touch w-full items-center border-s-4 border-transparent px-4 py-2 text-base font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>
