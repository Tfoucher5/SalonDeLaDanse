<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * `back` et `backLabel` affichent un lien de retour au-dessus du formulaire,
     * vers l'étape précédente du parcours.
     */
    public function __construct(
        public ?string $title = null,
        public string $width = 'sm:max-w-md',
        public ?string $back = null,
        public string $backLabel = 'Retour',
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
