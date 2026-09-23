@props([
    'title' => null,
    'width' => 'lg',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-layout.head :title="$title" />
    </head>

    <body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
        <a href="#contenu" class="skip-link print-hidden">Aller au contenu</a>

        {{-- Halos d'ambiance : terracotta en haut, emeraude en bas. Decor pur,
             ignores par les lecteurs d'ecran et par l'impression. --}}
        <div class="stage-glow print-hidden" aria-hidden="true"></div>

        <div class="relative flex min-h-screen flex-col">
            @include('layouts.navigation')

            @isset($header)
                <header>
                    <x-ui.container :size="$width" class="pb-2 pt-6 sm:pt-10">
                        {{ $header }}
                    </x-ui.container>
                </header>
            @endisset

            {{-- Le padding bas laisse la place a la barre d'onglets mobile. --}}
            <main id="contenu" class="flex-1 pb-28 pt-4 sm:pt-6 md:pb-12">
                <x-ui.container :size="$width" class="space-y-5 sm:space-y-6">
                    {{ $slot }}
                </x-ui.container>
            </main>

            <footer class="mt-auto hidden border-t border-zinc-900/5 bg-white/60 md:block print-hidden">
                <x-ui.container :size="$width" class="flex flex-wrap items-center justify-between gap-2 py-6 text-sm text-zinc-500">
                    <p><span class="font-semibold text-zinc-900">{{ config('app.name') }}</span> — Coordination bénévoles</p>
                    <p>JayDance Fam</p>
                </x-ui.container>
            </footer>
        </div>
    </body>
</html>
