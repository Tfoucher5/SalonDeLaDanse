{{-- Une ligne de `x-ui.data-list` : le libelle a gauche, la valeur a droite. --}}

@props(['label'])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-x-4 gap-y-1 py-3 first:pt-0 last:pb-0']) }}>
    <dt class="text-zinc-500">{{ $label }}</dt>
    <dd class="font-medium text-zinc-900">{{ $slot }}</dd>
</div>
