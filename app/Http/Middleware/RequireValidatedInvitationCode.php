<?php

namespace App\Http\Middleware;

use App\Services\VolunteerRegistrar;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ferme le formulaire d inscription a qui n a pas valide un code disponible.
 *
 * Le code est reverifie a chaque requete : un code consomme entre-temps par
 * quelqu un d autre renvoie sur l ecran de saisie.
 */
class RequireValidatedInvitationCode
{
    /**
     * Cle de session portant le code deja valide.
     */
    public const SESSION_KEY = 'invitation_code';

    public function __construct(private readonly VolunteerRegistrar $registrar) {}

    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->session()->get(self::SESSION_KEY);

        if (! is_string($code) || ! $this->registrar->codeIsAvailable($code)) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('register.code')->withErrors([
                'code' => "Ce code d'invitation n'est pas valide ou a déjà été utilisé.",
            ]);
        }

        return $next($request);
    }
}
