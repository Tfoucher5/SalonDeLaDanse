{{--
    Premier niveau de lecture de la grille sur mobile : un jour a la fois.

    L'onglet actif porte l'accent — c'est une commande, pas une information.
--}}

@props([
    'day',
    'selected' => false,
])

<a href="{{ route('planning.index', ['day' => $day->toDateString()]) }}"
   @if ($selected) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'tabular-grid flex h-14 min-h-touch flex-1 basis-24 flex-col items-center justify-center rounded-md border transition '.($selected
        ? 'border-primary bg-primary text-white'
        : 'border-zinc-200 bg-white text-zinc-900 hover:bg-zinc-100')]) }}>
    <span class="text-sm font-medium">{{ ucfirst($day->translatedFormat('D')) }}</span>
    <span class="text-xs {{ $selected ? 'text-white/80' : 'text-zinc-500' }}">{{ $day->translatedFormat('j M') }}</span>
</a>
