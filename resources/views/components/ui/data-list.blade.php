{{-- Liste de paires libelle / valeur, separees par une bordure. --}}

<dl {{ $attributes->merge(['class' => 'divide-y divide-zinc-200 text-sm tabular-grid']) }}>
    {{ $slot }}
</dl>
