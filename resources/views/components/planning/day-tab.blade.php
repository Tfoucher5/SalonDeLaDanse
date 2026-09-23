{{--
    Premier niveau de lecture de la grille sur mobile : un jour a la fois.

    Les onglets forment une commande segmentee ; l'actif se detache en carte
    blanche et porte l'accent — c'est une commande, pas une information.
--}}

@props([
    'day',
    'index' => 0,
    'selected' => false,
    'route' => 'planning.index',
    'params' => [],
])

{{-- Le back-office reutilise ces onglets sur sa propre route : le jour reste
     le premier niveau de lecture des deux cotes. Les attributs `data-day-*`
     ne servent qu'a la grille benevole, ou `planning.js` intercepte le clic
     pour changer de jour sans rechargement ; ailleurs, ils sont inertes. --}}
<a href="{{ route($route, ['day' => $day->toDateString()] + $params) }}"
   data-day-link
   data-day-index="{{ $index }}"
   @if ($selected) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'tabular-grid flex min-h-touch flex-1 flex-col items-center justify-center rounded-xl px-3 py-2 transition '.($selected
        ? 'bg-white text-primary shadow-card'
        : 'text-zinc-500 hover:text-zinc-900')]) }}>
    <span class="text-base font-bold leading-tight">{{ ucfirst($day->translatedFormat('D j')) }}</span>
    <span class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] {{ $selected ? 'text-zinc-500' : 'text-zinc-400' }}">{{ $day->translatedFormat('M Y') }}</span>
</a>
