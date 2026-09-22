<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\InvitationCodeUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireValidatedInvitationCode;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\VolunteerRegistrar;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        return view('auth.register', [
            'code' => $request->session()->get(RequireValidatedInvitationCode::SESSION_KEY),
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request, VolunteerRegistrar $registrar): RedirectResponse
    {
        $code = (string) $request->session()->get(RequireValidatedInvitationCode::SESSION_KEY);

        try {
            $user = $registrar->register(
                $code,
                $request->safe()->only(['first_name', 'last_name', 'email', 'phone', 'birth_date', 'password']),
                $request->file('photo'),
            );
        } catch (InvitationCodeUnavailableException $exception) {
            // Le code a ete consomme entre l affichage du formulaire et l envoi.
            $request->session()->forget(RequireValidatedInvitationCode::SESSION_KEY);

            return redirect()->route('register.code')
                ->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->forget(RequireValidatedInvitationCode::SESSION_KEY);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
