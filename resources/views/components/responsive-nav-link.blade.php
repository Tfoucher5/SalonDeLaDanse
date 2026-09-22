@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex w-full items-center min-h-[44px] ps-3 pe-4 py-2 border-s-4 border-primary text-start text-base font-medium text-primary bg-primary-soft focus:outline-none transition duration-150 ease-in-out'
            : 'flex w-full items-center min-h-[44px] ps-3 pe-4 py-2 border-s-4 border-transparent text-start text-base font-medium text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100 hover:border-zinc-200 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
