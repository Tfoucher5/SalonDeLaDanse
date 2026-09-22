<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Salon de la Danse') }}</title>

        <!-- Police -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-zinc-900">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 pt-10 pb-10 sm:pt-0 bg-zinc-50">
            <a href="/" class="text-xl font-semibold text-zinc-900">
                {{ config('app.name', 'Salon de la Danse') }}
            </a>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white border border-zinc-200 rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
