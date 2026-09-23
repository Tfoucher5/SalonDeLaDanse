{{--
    Premier niveau de lecture de la grille sur mobile : un jour a la fois.

    Les onglets forment une commande segmentee ; l'actif se detache en carte
    blanche et porte l'accent — c'est une commande, pas une information.
--}}

@props([
    'day',
    'selected' => false,
])

<a href="{{ route('planning.index', ['day' => $day->toDateString()]) }}"
   @if ($selected) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'tabular-grid flex min-h-touch flex-1 flex-col items-center justify-center rounded-xl px-3 py-2 transition '.($selected
        ? 'bg-white text-primary shadow-card'
        : 'text-zinc-500 hover:text-zinc-900')]) }}>
    <span class="text-base font-bold leading-tight">{{ ucfirst($day->translatedFormat('D j')) }}</span>
    <span class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] {{ $selected ? 'text-zinc-500' : 'text-zinc-400' }}">{{ $day->translatedFormat('M Y') }}</span>
</a>
