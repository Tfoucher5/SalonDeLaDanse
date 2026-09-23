<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Le layout du compte connecte.
 *
 * Les ecrans propres au benevole et ceux propres a l administrateur declarent
 * leur layout en clair. Restent les quelques pages partagees — le profil, la
 * planche de la charte : chacun doit y retrouver son en-tete, et un
 * administrateur n a rien a faire dans une navigation de benevole.
 */
class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view(Auth::user()?->isAdmin() ? 'layouts.admin' : 'layouts.app');
    }
}
