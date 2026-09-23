{{--
    Surface de base de l'application : blanche, arrondie, posee sur le fond
    porcelaine par un anneau tres leger et une ombre chaude teintee prune.

    `kicker` est la petite ligne en capitales sous le titre ; le slot `icon`
    loge une pastille d'illustration a gauche du titre.
--}}

@props([
    'title' => null,
    'subtitle' => null,
    'kicker' => null,
    'padding' => 'p-5 sm:p-6',
])

@php $hasHeader = $title !== null || $subtitle !== null || $kicker !== null || isset($actions) || isset($icon); @endphp

<section {{ $attributes->merge(['class' => 'rounded-2xl bg-white shadow-card ring-1 ring-zinc-900/5 '.$padding]) }}>
    @if ($hasHeader)
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
                @isset($icon)
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary" aria-hidden="true">
                        {{ $icon }}
                    </span>
                @endisset

                <div class="min-w-0">
                    @if ($title !== null)
                        <h2 class="text-lg font-bold tracking-tight text-zinc-900 sm:text-xl">{{ $title }}</h2>
                    @endif

                    @if ($kicker !== null)
                        <p class="mt-0.5 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">{{ $kicker }}</p>
                    @endif

                    @if ($subtitle !== null)
                        <p class="mt-1 text-sm text-zinc-500">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            @isset($actions)
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    @if (trim($slot) !== '')
        <div @class(['mt-5' => $hasHeader])>{{ $slot }}</div>
    @endif

    @isset($footer)
        <div class="mt-5 border-t border-zinc-200 pt-4">{{ $footer }}</div>
    @endisset
</section>
