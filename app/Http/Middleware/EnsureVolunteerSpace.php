<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renvoie l administrateur vers le back-office.
 *
 * Les ecrans du benevole ne le concernent pas : il n a ni planning a composer
 * ni fiche recapitulative, et ces pages ne lui montreraient que du vide. Ce
 * n est pas une interdiction mais une orientation — d ou la redirection
 * plutot que le 403 que renvoie la porte inverse, `can:admin`.
 */
class EnsureVolunteerSpace
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
