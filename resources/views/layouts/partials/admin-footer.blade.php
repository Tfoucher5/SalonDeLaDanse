{{--
    Pied de page du back-office, construit comme celui de l'espace benevole :
    l'outil, ses rubriques, et les liens de service.

    Sur mobile il reste visible au-dessus de la barre d'onglets fixe, grace a
    son padding bas.
--}}

<footer class="mt-auto border-t border-zinc-900/5 bg-white pb-24 md:pb-0 print-hidden">
    <x-ui.container :size="$width" class="grid gap-8 py-10 sm:grid-cols-2 lg:grid-cols-4">
        <div class="space-y-3 lg:col-span-2">
            <x-application-logo class="h-12" />

            <p class="max-w-sm text-sm text-zinc-500">
                Back-office de coordination des bénévoles du Salon de la Danse d'Angers :
                plannings, missions et exports pour l'équipe organisatrice.
            </p>
        </div>

        <nav aria-label="Rubriques du back-office">
            <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-900">Administration</p>

            <ul class="mt-3 space-y-1 text-sm">
                @foreach ([
                    'admin.dashboard' => "Vue d'ensemble",
                    'admin.planning' => 'Planning du Salon',
                    'admin.volunteers.index' => 'Bénévoles',
                    'admin.missions.index' => 'Missions',
                    'admin.badges.index' => 'Badges',
                ] as $routeName => $label)
                    <li>
                        <a href="{{ route($routeName) }}" class="inline-flex min-h-[2rem] items-center text-zinc-500 transition hover:text-primary">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <nav aria-label="Liens de service">
            <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-900">Mon compte</p>

            <ul class="mt-3 space-y-1 text-sm">
                <li>
                    <a href="{{ route('profile.edit') }}" class="inline-flex min-h-[2rem] items-center text-zinc-500 transition hover:text-primary">Mon profil</a>
                </li>
                <li>
                    <a href="{{ route('legal.notice') }}" class="inline-flex min-h-[2rem] items-center text-zinc-500 transition hover:text-primary">Mentions légales</a>
                </li>
            </ul>
        </nav>
    </x-ui.container>

    <div class="border-t border-zinc-900/5">
        <x-ui.container :size="$width" class="flex flex-col gap-1 py-5 text-xs text-zinc-500 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} {{ config('app.name') }}</p>
            <p>Espace réservé à l'équipe organisatrice.</p>
        </x-ui.container>
    </div>
</footer>
