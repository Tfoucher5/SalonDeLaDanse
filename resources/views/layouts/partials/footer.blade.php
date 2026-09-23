{{--
    Pied de page de l'espace connecte : qui organise, ou aller, qui contacter.

    Sur mobile il reste visible, au-dessus de la barre d'onglets fixe grace a
    son padding bas. Les coordonnees viennent de config('salon.contact') : une
    valeur absente est simplement masquee.
--}}

@php $contact = array_filter(config('salon.contact')); @endphp

<footer class="mt-auto border-t border-zinc-900/5 bg-white pb-24 md:pb-0 print-hidden">
    <x-ui.container :size="$width" class="grid gap-8 py-10 sm:grid-cols-2 lg:grid-cols-4">
        <div class="space-y-3 lg:col-span-2">
            <x-application-logo class="h-12" />

            <p class="max-w-sm text-sm text-zinc-500">
                Plateforme de coordination des bénévoles du Salon de la Danse d'Angers,
                au Centre de Congrès.
            </p>
        </div>

        <nav aria-label="Liens du pied de page">
            <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-900">Mon espace</p>

            <ul class="mt-3 space-y-1 text-sm">
                @foreach ([
                    'dashboard' => 'Tableau de bord',
                    'planning.index' => 'Réservation de créneaux',
                    'planning.summary' => 'Mon planning',
                    'profile.edit' => 'Mon profil',
                ] as $routeName => $label)
                    <li>
                        <a href="{{ route($routeName) }}" class="inline-flex min-h-[2rem] items-center text-zinc-500 transition hover:text-primary">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div>
            <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-900">Équipe organisatrice</p>

            <ul class="mt-3 space-y-1 text-sm text-zinc-500">
                @isset($contact['name'])
                    <li class="min-h-[2rem] py-1.5">{{ $contact['name'] }}</li>
                @endisset

                @isset($contact['email'])
                    <li>
                        <a href="mailto:{{ $contact['email'] }}" class="inline-flex min-h-[2rem] items-center break-all transition hover:text-primary">{{ $contact['email'] }}</a>
                    </li>
                @endisset

                @isset($contact['phone'])
                    <li>
                        <a href="tel:{{ preg_replace('/\s+/', '', $contact['phone']) }}" class="inline-flex min-h-[2rem] items-center tabular-grid transition hover:text-primary">{{ $contact['phone'] }}</a>
                    </li>
                @endisset

                @if ($contact === [])
                    <li class="py-1.5">Coordonnées bientôt disponibles.</li>
                @endif
            </ul>
        </div>
    </x-ui.container>

    <div class="border-t border-zinc-900/5">
        <x-ui.container :size="$width" class="flex flex-col gap-1 py-5 text-xs text-zinc-500 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} {{ config('app.name') }}</p>
            <p>Espace réservé aux bénévoles invités par l'équipe organisatrice.</p>
        </x-ui.container>
    </div>
</footer>
