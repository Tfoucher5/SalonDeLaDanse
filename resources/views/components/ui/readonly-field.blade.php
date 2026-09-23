{{--
    Donnee verrouillee, affichee en tuile : un libelle en capitales, la valeur
    en clair. Pour ce qui ne se modifie que par l'equipe organisatrice.
--}}

@props(['label'])

<div {{ $attributes->merge(['class' => 'min-w-0 rounded-xl bg-zinc-50 px-4 py-3 tabular-grid']) }}>
    <dt class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">{{ $label }}</dt>
    <dd class="mt-1 truncate text-base font-medium text-zinc-900">{{ $slot }}</dd>
</div>
