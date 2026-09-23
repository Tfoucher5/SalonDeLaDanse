<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\VolunteerAccount;
use Illuminate\Http\RedirectResponse;

/**
 * Réinitialisation des identifiants d'un bénévole.
 *
 * Le MVP n'envoie aucun e-mail : le mot de passe temporaire s'affiche une seule
 * fois à l'administrateur, qui le transmet lui-même. Il transite par la session
 * flash, jamais par l'URL ni par un enregistrement en base.
 */
class VolunteerCredentialsController extends Controller
{
    public function __construct(private readonly VolunteerAccount $accounts) {}

    public function __invoke(User $volunteer): RedirectResponse
    {
        $password = $this->accounts->resetPassword($volunteer);

        return back()
            ->with('status', 'Mot de passe réinitialisé. Transmettez-le au bénévole : il ne sera plus affiché.')
            ->with('temporary_password', $password);
    }
}
