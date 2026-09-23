<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Planche de reference de la charte graphique.
 *
 * Elle n'existe que hors production : c'est un outil de travail, pas un ecran
 * du produit. Chaque composant du kit y figure dans tous ses etats, pour qu'une
 * page nouvelle se compose en piochant ici plutot qu'en reecrivant des classes.
 */
class DesignSystemController extends Controller
{
    public function __invoke(): View
    {
        return view('design-system.index', [
            'neutrals' => [
                ['zinc-50', '#FAFAF7', 'Fond porcelaine de la page', 'bg-zinc-50'],
                ['zinc-100', '#F4F0EC', 'Tuiles, champs en lecture seule, survol', 'bg-zinc-100'],
                ['zinc-200', '#EAE3DE', 'Séparateurs, pistes de jauge', 'bg-zinc-200'],
                ['zinc-400', '#A59CA1', 'Icônes secondaires, texte désactivé', 'bg-zinc-400'],
                ['zinc-500', '#716B70', 'Texte secondaire, libellés', 'bg-zinc-500'],
                ['zinc-900', '#1F1A1C', 'Texte courant et titres', 'bg-zinc-900'],
            ],
            'accents' => [
                ['primary', '#B93A24', 'Boutons primaires, liens, onglet actif', 'bg-primary'],
                ['primary-hover', '#9A2C19', 'Survol', 'bg-primary-hover'],
                ['primary-bright', '#E0533C', 'Terracotta de marque, logo, anneau de focus', 'bg-primary-bright'],
                ['primary-soft', '#FDEBE7', 'Fond de badge, onglet actif, halo', 'bg-primary-soft'],
                ['plum', '#6C2E58', 'États du planning, étiquettes de section', 'bg-plum'],
                ['plum-soft', '#F6E8F1', 'Fond des capsules prune', 'bg-plum-soft'],
            ],
            'functionals' => [
                ['gauge-free', '#0F766E', 'Places disponibles', 'bg-gauge-free'],
                ['gauge-tight', '#B45309', 'Presque complet', 'bg-gauge-tight'],
                ['gauge-full', '#716B70', 'Complet ou indisponible', 'bg-gauge-full'],
                ['danger', '#B91C1C', 'Erreur de validation, action destructrice', 'bg-danger'],
            ],
            // Un paginateur de demonstration : la planche montre le composant
            // dans son etat le plus courant, une page au milieu d'une liste.
            'paginator' => new LengthAwarePaginator(range(1, 25), 130, 25, 2, ['path' => url()->current()]),
        ]);
    }
}
