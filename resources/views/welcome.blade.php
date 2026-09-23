<x-guest-layout width="sm:max-w-lg">
    <x-ui.form-heading
        title="Espace bénévoles"
        subtitle="Composez votre planning du Salon à votre rythme, depuis votre téléphone." />

    {{-- Le parcours en trois temps : un candidat retenu doit comprendre en un
         coup d'oeil pourquoi on lui demande un code. --}}
    <ol class="space-y-2">
        @foreach ([
            ['Recevez votre code', 'Envoyé par e-mail une fois votre candidature retenue.'],
            ['Créez votre compte', 'Le code ne sert qu\'une seule fois.'],
            ['Choisissez vos créneaux', 'Entre 1 et 3 missions selon vos disponibilités.'],
        ] as $index => [$title, $description])
            <li class="flex items-center gap-3 rounded-xl bg-zinc-50 p-3">
                <span class="tabular-grid flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary">
                    {{ $index + 1 }}
                </span>

                <div>
                    <p class="font-semibold text-zinc-900">{{ $title }}</p>
                    <p class="mt-0.5 text-sm text-zinc-500">{{ $description }}</p>
                </div>
            </li>
        @endforeach
    </ol>

    @if (Route::has('login'))
        <div class="mt-5 grid grid-cols-2 gap-3">
            @auth
                <x-ui.button :href="url('/dashboard')" variant="primary" size="touch" block class="col-span-2">
                    Accéder à mon espace
                </x-ui.button>
            @else
                <x-ui.button :href="route('login')" variant="primary" size="touch" block>
                    Se connecter
                </x-ui.button>

                @if (Route::has('register'))
                    <x-ui.button :href="route('register.code')" size="touch" block>
                        M'inscrire
                    </x-ui.button>
                @endif
            @endauth
        </div>
    @endif
</x-guest-layout>
