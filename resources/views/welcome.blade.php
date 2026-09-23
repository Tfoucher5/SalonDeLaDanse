<x-guest-layout width="sm:max-w-lg">
    <h1 class="text-2xl font-semibold text-zinc-900">Espace bénévoles</h1>

    <p class="mt-2 text-zinc-500">
        Composez votre planning sur les trois jours du Salon, à votre rythme,
        depuis votre téléphone.
    </p>

    {{-- Le parcours en trois temps : un candidat retenu doit comprendre en un
         coup d'oeil pourquoi on lui demande un code. --}}
    <ol class="mt-6 space-y-4 border-t border-zinc-200 pt-6">
        @foreach ([
            ['Recevez votre code', "L'équipe organisatrice vous l'envoie par e-mail une fois votre candidature retenue."],
            ['Créez votre compte', 'Le code ouvre le formulaire d\'inscription. Il ne sert qu\'une seule fois.'],
            ['Choisissez vos créneaux', 'Entre 1 et 3 missions selon vos disponibilités, puis validez votre planning.'],
        ] as $index => [$title, $description])
            <li class="flex gap-3">
                <span class="tabular-grid flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-zinc-200 text-sm font-medium text-zinc-500">
                    {{ $index + 1 }}
                </span>

                <div>
                    <p class="font-medium text-zinc-900">{{ $title }}</p>
                    <p class="mt-0.5 text-sm text-zinc-500">{{ $description }}</p>
                </div>
            </li>
        @endforeach
    </ol>

    @if (Route::has('login'))
        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            @auth
                <x-ui.button :href="url('/dashboard')" variant="primary" size="touch" class="sm:w-auto" block>
                    Accéder à mon espace
                </x-ui.button>
            @else
                <x-ui.button :href="route('login')" variant="primary" size="touch" block>
                    Se connecter
                </x-ui.button>

                @if (Route::has('register'))
                    <x-ui.button :href="route('register.code')" size="touch" block>
                        Créer mon compte
                    </x-ui.button>
                @endif
            @endauth
        </div>
    @endif
</x-guest-layout>
