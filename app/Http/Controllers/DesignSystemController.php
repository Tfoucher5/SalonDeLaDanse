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
                ['zinc-50', '#FAFAFA', 'Fond de page', 'bg-zinc-50'],
                ['zinc-100', '#F4F4F5', 'Fonds de section, survol', 'bg-zinc-100'],
                ['zinc-200', '#E4E4E7', 'Bordures et séparateurs', 'bg-zinc-200'],
                ['zinc-400', '#A1A1AA', 'Icônes secondaires, texte désactivé', 'bg-zinc-400'],
                ['zinc-500', '#71717A', 'Texte secondaire, libellés', 'bg-zinc-500'],
                ['zinc-900', '#18181B', 'Texte courant et titres', 'bg-zinc-900'],
            ],
            'accents' => [
                ['primary', '#4338CA', 'Boutons primaires, liens, onglet actif', 'bg-primary'],
                ['primary-hover', '#3730A3', 'Survol', 'bg-primary-hover'],
                ['primary-ring', '#4F46E5', 'Anneau de focus', 'bg-primary-ring'],
                ['primary-soft', '#EEF2FF', 'Fond de badge, ligne sélectionnée', 'bg-primary-soft'],
            ],
            'functionals' => [
                ['gauge-free', '#15803D', 'Places disponibles', 'bg-gauge-free'],
                ['gauge-tight', '#B45309', 'Presque complet', 'bg-gauge-tight'],
                ['gauge-full', '#71717A', 'Complet ou indisponible', 'bg-gauge-full'],
                ['danger', '#B91C1C', 'Erreur de validation, action destructrice', 'bg-danger'],
            ],
            // Un paginateur de demonstration : la planche montre le composant
            // dans son etat le plus courant, une page au milieu d'une liste.
            'paginator' => new LengthAwarePaginator(range(1, 25), 130, 25, 2, ['path' => url()->current()]),
        ]);
    }
}
