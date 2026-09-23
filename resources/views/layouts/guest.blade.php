<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-layout.head :title="$title" />
    </head>

    {{--
        Pages d'authentification : tout tient dans la hauteur de l'ecran, sans
        defilement. En haut, le retour a gauche et le logo cliquable au centre ;
        au milieu, la carte du formulaire ; en bas, une ligne discrete.
    --}}
    <body class="min-h-dvh bg-zinc-50 font-sans text-zinc-900 antialiased">
        <div class="stage-glow" aria-hidden="true"></div>

        <div class="relative mx-auto flex min-h-dvh w-full flex-col px-4 py-3 sm:py-6 {{ $width }}">
            <header class="grid h-14 shrink-0 grid-cols-[1fr_auto_1fr] items-center gap-2">
                <div>
                    @if ($back !== null)
                        <x-ui.back-link :href="$back">{{ $backLabel }}</x-ui.back-link>
                    @endif
                </div>

                <a href="{{ url('/') }}" class="rounded-xl" aria-label="{{ config('app.name') }} — accueil">
                    <x-application-logo class="h-10 sm:h-12" />
                </a>

                <div></div>
            </header>

            <main class="flex flex-1 flex-col justify-center py-3 sm:py-6">
                <div class="rounded-3xl bg-white p-5 shadow-card ring-1 ring-zinc-900/5 sm:p-8">
                    {{ $slot }}
                </div>
            </main>

            <footer class="shrink-0 text-center text-xs text-zinc-500">
                © {{ now()->year }} {{ config('app.name') }} · Espace bénévoles ·
                <a href="{{ route('legal.notice') }}" class="font-semibold transition hover:text-primary">Mentions légales</a>
            </footer>
        </div>
    </body>
</html>
