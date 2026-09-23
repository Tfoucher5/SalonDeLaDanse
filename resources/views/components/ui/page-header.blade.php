{{-- En-tete de page : de quoi il s'agit, et l'action principale s'il y en a une. --}}

@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 md:flex-row md:items-end md:justify-between']) }}>
    <div class="min-w-0">
        @if ($eyebrow !== null)
            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-plum-soft px-3 py-1 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-plum">
                <span class="h-1.5 w-1.5 rounded-full bg-plum" aria-hidden="true"></span>
                {{ $eyebrow }}
            </p>
        @endif

        <h1 class="text-[1.75rem] font-extrabold leading-tight tracking-tight text-zinc-900 sm:text-4xl">{{ $title }}</h1>

        @if ($subtitle !== null)
            <p class="mt-1.5 text-base text-zinc-500 sm:text-lg">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
