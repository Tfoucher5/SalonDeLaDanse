{{--
    En-tete d'un formulaire d'authentification : ou l'on en est du parcours,
    ce qu'on demande, et pourquoi.
--}}

@props([
    'title',
    'subtitle' => null,
    'step' => null,
])

<div {{ $attributes->merge(['class' => 'mb-5']) }}>
    @if ($step !== null)
        <p class="mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">{{ $step }}</p>
    @endif

    <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-[1.75rem]">{{ $title }}</h1>

    @if ($subtitle !== null)
        <p class="mt-2 text-sm text-zinc-500 sm:text-[0.9375rem]">{{ $subtitle }}</p>
    @endif

    {{ $slot }}
</div>
