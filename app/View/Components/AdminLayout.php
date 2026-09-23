<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Layout du back-office.
 *
 * Distinct de celui du benevole : sa navigation, sa marque et son pied de page
 * disent en permanence qu'on est du cote de l'organisation.
 */
class AdminLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.admin');
    }
}
