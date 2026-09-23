@props([
    'title' => null,
    'width' => 'xl',
])

{{--
    Layout du back-office. Il partage l'ambiance de l'espace benevole — meme
    fond, memes halos, meme rythme vertical — mais garde sa navigation, sa
    marque et son pied de page : on doit savoir en permanence de quel cote de
    l'outil on se trouve.

    Une divergence assumee : pas de `partials.toasts` ici. Les comptes rendus du
    back-office sont longs et consequents — la regle qu'une attribution vient
    d'outrepasser, le mot de passe temporaire a recopier — et une notification
    qui s'efface toute seule les ferait perdre. Ils s'affichent en clair dans la
    page, et y restent.
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-layout.head :title="$title" />
    </head>

    <body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
        <a href="#contenu" class="skip-link print-hidden">Aller au contenu</a>

        <div class="stage-glow print-hidden" aria-hidden="true"></div>

        <div class="relative flex min-h-screen flex-col">
            @include('layouts.admin-navigation')

            @isset($header)
                <header>
                    <x-ui.container :size="$width" class="pb-2 pt-6 sm:pt-10">
                        {{ $header }}
                    </x-ui.container>
                </header>
            @endisset

            <main id="contenu" class="flex-1 pb-12 pt-4 sm:pt-6">
                <x-ui.container :size="$width" class="space-y-5 sm:space-y-6">
                    {{ $slot }}
                </x-ui.container>
            </main>

            {{-- Toujours a la largeur de la navigation : le pied de page ne
                 doit pas changer de taille d'un ecran a l'autre. --}}
            @include('layouts.partials.admin-footer', ['width' => 'xl'])
        </div>
    </body>
</html>
