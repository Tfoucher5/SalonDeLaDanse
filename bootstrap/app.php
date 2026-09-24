<?php

use App\Http\Middleware\EnsureRegistrationIsOpen;
use App\Http\Middleware\EnsureVolunteerSpace;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'registration.open' => EnsureRegistrationIsOpen::class,
            'volunteer.space' => EnsureVolunteerSpace::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Un QR code retouche ou recopie de travers : la page de verification
        // le dit en clair a l agent d accueil, plutot qu un 403 generique.
        $exceptions->render(fn (InvalidSignatureException $exception, Request $request) => $request->routeIs('badges.verify')
            ? response()->view('badges.unrecognized', status: 403)
            : null);
    })->create();
