{{-- Rien a afficher, et la raison. Un vide sans explication est un bug percu. --}}

@props(['title'])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-zinc-300 bg-white/60 p-8 text-center']) }}>
    <p class="font-semibold text-zinc-900">{{ $title }}</p>

    @if (trim($slot) !== '')
        <div class="mx-auto mt-1 max-w-md text-sm text-zinc-500">{{ $slot }}</div>
    @endif
</div>
