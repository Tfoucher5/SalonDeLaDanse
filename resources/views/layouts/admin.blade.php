@props([
    'title' => null,
    'width' => 'xl',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-layout.head :title="$title" />
    </head>

    <body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
        <a href="#contenu" class="skip-link print-hidden">Aller au contenu</a>

        <div class="flex min-h-screen flex-col">
            @include('layouts.admin-navigation')

            @isset($header)
                <header class="border-b border-zinc-200 bg-white">
                    <x-ui.container :size="$width" class="py-6 sm:py-8">
                        {{ $header }}
                    </x-ui.container>
                </header>
            @endisset

            <main id="contenu" class="flex-1 py-6 sm:py-8">
                <x-ui.container :size="$width" class="space-y-6">
                    {{ $slot }}
                </x-ui.container>
            </main>

            <footer class="mt-auto border-t border-zinc-200 bg-white print-hidden">
                <x-ui.container :size="$width" class="flex flex-wrap items-center justify-between gap-2 py-6 text-sm text-zinc-500">
                    <p>{{ config('app.name') }} — JayDance Fam</p>
                    <p>Back-office</p>
                </x-ui.container>
            </footer>
        </div>
    </body>
</html>
