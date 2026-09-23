{{--
    Pagination d'une liste d'administration.

    Deux commandes et un compteur, rien de plus : une longue serie de numeros
    n'aide personne a retrouver un benevole, la recherche s'en charge.
--}}

@props(['paginator'])

@if ($paginator->total() > 0)
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3']) }}>
        <p class="text-sm text-zinc-500 tabular-grid">
            Résultats {{ $paginator->firstItem() }} à {{ $paginator->lastItem() }}
            sur {{ $paginator->total() }}
        </p>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-2">
                @if ($paginator->onFirstPage())
                    <x-ui.button type="button" disabled>Précédent</x-ui.button>
                @else
                    <x-ui.button :href="$paginator->previousPageUrl()" rel="prev">Précédent</x-ui.button>
                @endif

                @if ($paginator->hasMorePages())
                    <x-ui.button :href="$paginator->nextPageUrl()" rel="next">Suivant</x-ui.button>
                @else
                    <x-ui.button type="button" disabled>Suivant</x-ui.button>
                @endif
            </div>
        @endif
    </div>
@endif
