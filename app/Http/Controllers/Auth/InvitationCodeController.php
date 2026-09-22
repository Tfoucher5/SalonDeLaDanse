<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireValidatedInvitationCode;
use App\Http\Requests\Auth\InvitationCodeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Ecran de saisie du code d invitation, en amont du formulaire d inscription.
 */
class InvitationCodeController extends Controller
{
    public function create(): View
    {
        return view('auth.invitation-code');
    }

    public function store(InvitationCodeRequest $request): RedirectResponse
    {
        $request->session()->put(
            RequireValidatedInvitationCode::SESSION_KEY,
            $request->validatedCode(),
        );

        return redirect()->route('register');
    }
}
