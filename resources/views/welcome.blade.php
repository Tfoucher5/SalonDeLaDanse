<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Salon de la Danse') }}</title>

        <!-- Police -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-zinc-900">
        <div class="min-h-screen bg-zinc-50 flex flex-col items-center justify-center px-4 py-10">
            <div class="w-full sm:max-w-md bg-white border border-zinc-200 rounded-lg p-6">
                <h1 class="text-2xl font-semibold text-zinc-900">
                    {{ config('app.name', 'Salon de la Danse') }}
                </h1>

                <p class="mt-2 text-sm text-zinc-500">
                    Espace bénévoles. Composez votre planning sur les trois jours du Salon.
                </p>

                @if (Route::has('login'))
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="inline-flex items-center justify-center h-10 px-4 bg-primary rounded-md font-medium text-sm text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary-ring focus:ring-offset-2">
                                Accéder à mon espace
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center justify-center h-10 px-4 bg-primary rounded-md font-medium text-sm text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary-ring focus:ring-offset-2">
                                Se connecter
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register.code') }}"
                                   class="inline-flex items-center justify-center h-10 px-4 bg-white border border-zinc-200 rounded-md font-medium text-sm text-zinc-900 hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-primary-ring focus:ring-offset-2">
                                    Créer mon compte
                                </a>
                            @endif
                        @endauth
                    </div>

                    <p class="mt-6 text-sm text-zinc-500">
                        La création de compte nécessite un code d'invitation envoyé par l'équipe organisatrice.
                    </p>
                @endif
            </div>
        </div>
    </body>
</html>
