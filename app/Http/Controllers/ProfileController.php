<?php

namespace App\Http\Controllers;

use App\Enums\PlanningState;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Le profil du bénévole, et à côté l'état de son planning.
     *
     * Le mot de passe ne se change pas ici : le parcours « mot de passe oublié »
     * de la page de connexion est le seul chemin, par e-mail.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $edition = $user->activeEdition();

        return view('profile.edit', [
            'user' => $user,
            'edition' => $edition,
            'state' => PlanningState::for($user, $edition),
            'bookedCount' => $user->assignments()->count(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
