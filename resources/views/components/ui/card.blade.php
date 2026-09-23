{{--
    Surface de base de l'application : blanche sur fond zinc-50, delimitee par
    une bordure. Jamais d'ombre ici, c'est le contraste de surface qui structure
    la page.
--}}

@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'p-5 sm:p-6',
])

@php $hasHeader = $title !== null || $subtitle !== null || isset($actions); @endphp

<section {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white '.$padding]) }}>
    @if ($hasHeader)
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                @if ($title !== null)
                    <h2 class="text-lg font-semibold text-zinc-900">{{ $title }}</h2>
                @endif

                @if ($subtitle !== null)
                    <p class="mt-1 text-sm text-zinc-500">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    @if (trim($slot) !== '')
        <div @class(['mt-4' => $hasHeader])>{{ $slot }}</div>
    @endif

    @isset($footer)
        <div class="mt-5 border-t border-zinc-200 pt-4">{{ $footer }}</div>
    @endisset
</section>
