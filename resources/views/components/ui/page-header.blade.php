{{-- En-tete de page : de quoi il s'agit, et l'action principale s'il y en a une. --}}

@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($eyebrow !== null)
            <p class="text-sm font-medium text-zinc-500">{{ $eyebrow }}</p>
        @endif

        <h1 class="text-2xl font-semibold text-zinc-900 sm:text-3xl">{{ $title }}</h1>

        @if ($subtitle !== null)
            <p class="mt-1 text-sm text-zinc-500">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
