<?php

namespace App\Http\Middleware;

use App\Enums\PlanningState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ferme la composition du planning hors fenetre d inscription.
 *
 * A poser sur les seules routes qui ecrivent le planning : la grille reste
 * consultable en permanence, seule la modification est bloquee. Le verrouillage
 * manuel de l edition et la validation definitive du benevole passent par le
 * meme chemin, via PlanningState.
 */
class EnsureRegistrationIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Le middleware se pose toujours derriere 'auth' ; la garde evite
        // simplement une erreur obscure si l ordre venait a changer.
        abort_if($user === null, Response::HTTP_FORBIDDEN);

        $state = PlanningState::for($user, $user->activeEdition());

        if (! $state->isEditable()) {
            abort(Response::HTTP_FORBIDDEN, $state->description());
        }

        return $next($request);
    }
}
