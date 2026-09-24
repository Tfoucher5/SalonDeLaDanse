<?php

namespace App\Enums;

/**
 * Qui reçoit un badge dans une génération groupée.
 *
 * `Filters` est le mode principal : le serveur recalcule la liste depuis les
 * critères de recherche, sans dépendre de cases cochées dans le navigateur.
 * `Selection` imprime seulement les bénévoles cochés à la main.
 */
enum BadgeSelection: string
{
    case Filters = 'filters';
    case Selection = 'selection';
}
