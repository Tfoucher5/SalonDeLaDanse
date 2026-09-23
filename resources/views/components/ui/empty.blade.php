{{-- Rien a afficher, et la raison. Un vide sans explication est un bug percu. --}}

@props(['title'])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-zinc-200 bg-white p-8 text-center']) }}>
    <p class="font-medium text-zinc-900">{{ $title }}</p>

    @if (trim($slot) !== '')
        <div class="mx-auto mt-1 max-w-md text-sm text-zinc-500">{{ $slot }}</div>
    @endif
</div>
