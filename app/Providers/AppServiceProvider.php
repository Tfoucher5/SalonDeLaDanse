<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // La porte du back-office. Un seul point de verite : les routes
        // d administration passent toutes par `can:admin`, jamais par un test
        // de role recopie dans un controleur ou dans une vue.
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());

        // Meme regle pour qui rouvre une page d invite en etant deja connecte :
        // chaque role rentre chez lui, jamais chez l autre.
        RedirectIfAuthenticated::redirectUsing(
            fn (Request $request): string => route($request->user()->homeRoute()),
        );
    }
}
