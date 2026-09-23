{{--
    Marque de la plateforme : trois barres d'un planning, de la plus longue a la
    plus courte. Neutre par construction — l'identite vient du logo et du
    contenu, l'accent indigo reste reserve a ce sur quoi on peut cliquer.
--}}

<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
     {{ $attributes->merge(['class' => 'shrink-0']) }}>
    <rect width="32" height="32" rx="8" class="fill-zinc-900" />
    <rect x="8" y="9" width="16" height="3.5" rx="1.75" class="fill-white" />
    <rect x="8" y="14.25" width="11" height="3.5" rx="1.75" class="fill-white" opacity="0.72" />
    <rect x="8" y="19.5" width="7" height="3.5" rx="1.75" class="fill-white" opacity="0.48" />
</svg>
