<x-app-layout title="Tableau de bord">
    <x-slot name="header">
        {{-- Accueil : qui l'on est, ou l'on est, et l'action qui compte. --}}
        <section class="relative overflow-hidden rounded-3xl bg-white p-6 shadow-card ring-1 ring-zinc-900/5 sm:p-8">
            <div class="pointer-events-none absolute -right-10 top-1/2 h-56 w-56 -translate-y-1/2 rounded-full bg-gauge-free/10 blur-3xl" aria-hidden="true"></div>

            <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="min-w-0">
                    @if ($edition)
                        <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-plum-soft px-3 py-1 text-xs font-semibold text-plum">
                            {{ $edition->name }}
                        </p>
                    @endif

                    <h1 class="text-[2rem] font-extrabold leading-tight tracking-tight text-zinc-900 sm:text-5xl">
                        Bonjour <span class="text-primary underline decoration-primary-soft decoration-[6px] underline-offset-4">{{ $user->first_name }}</span> !
                    </h1>

                    <p class="mt-2 text-base text-zinc-900 sm:text-lg">
                        @if ($edition)
                            Bienvenue sur votre espace bénévole.
                        @else
                            {{ "Aucune édition n'est ouverte pour le moment." }}
                        @endif
                    </p>
                </div>

                <div class="flex w-full shrink-0 flex-col gap-2 sm:flex-row md:w-auto">
                    <x-ui.button :href="route('planning.summary')" size="touch" class="w-full sm:w-auto">
                        Ma fiche
                    </x-ui.button>

                    <x-ui.button :href="route('planning.index')" variant="primary" size="touch" class="w-full sm:w-auto">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                        </svg>
                        Accéder au planning
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L13.6 11H4a1 1 0 110-2h9.6l-3.3-3.3a1 1 0 010-1.4z" clip-rule="evenodd" />
                        </svg>
                    </x-ui.button>
                </div>
            </div>
        </section>
    </x-slot>

    {{-- L'etat du planning : la premiere chose a savoir en arrivant. --}}
    <x-ui.card title="Mon planning">
        <x-slot name="actions">
            <x-ui.badge :tone="$state->tone()" dot>{{ $state->label() }}</x-ui.badge>
        </x-slot>

        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <p class="max-w-xl text-zinc-600">{{ $state->description() }}</p>

            @if ($edition)
                @php $maximum = $edition->max_slots_per_volunteer; @endphp

                <div class="rounded-xl bg-zinc-50 p-4 tabular-grid lg:w-80">
                    <p class="flex items-baseline justify-between gap-2 text-sm">
                        <span class="font-bold text-zinc-900">{{ $bookedCount }} / {{ $maximum }} créneaux choisis</span>
                        <span class="text-zinc-500">min. {{ $edition->min_slots_per_volunteer }}</span>
                    </p>

                    <div class="mt-3 flex gap-1.5" role="img" aria-label="{{ $bookedCount }} créneau{{ $bookedCount > 1 ? 'x' : '' }} sur {{ $maximum }}">
                        @for ($segment = 1; $segment <= $maximum; $segment++)
                            <span @class([
                                'h-2 flex-1 rounded-full',
                                'bg-primary' => $segment <= $bookedCount,
                                'bg-zinc-200' => $segment > $bookedCount,
                            ])></span>
                        @endfor
                    </div>
                </div>
            @endif
        </div>

        @unless ($state->isEditable())
            <x-ui.alert class="mt-4">
                Le planning est en <strong class="font-semibold text-zinc-900">consultation seule</strong> :
                vous pouvez le lire, mais aucune modification n'est possible.
            </x-ui.alert>
        @endunless
    </x-ui.card>

    @if ($edition)
        <div class="grid gap-5 sm:gap-6 lg:grid-cols-5">
            <x-ui.card title="Dates du Salon" :kicker="$edition->name" class="lg:col-span-2">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                    </svg>
                </x-slot>

                <x-slot name="actions">
                    <x-ui.badge>{{ $edition->days()->count() }} jours</x-ui.badge>
                </x-slot>

                <ul class="space-y-2.5">
                    @foreach ($edition->days() as $day)
                        <li class="flex items-center gap-3 rounded-xl bg-zinc-50 p-3 tabular-grid">
                            <span class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-white shadow-card">
                                <span class="text-[0.625rem] font-bold uppercase leading-none text-primary">{{ $day->translatedFormat('D') }}</span>
                                <span class="text-base font-extrabold leading-tight text-zinc-900">{{ $day->format('j') }}</span>
                            </span>
                            <span class="font-semibold text-zinc-900">{{ ucfirst($day->translatedFormat('l j F Y')) }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>

            <x-ui.card title="Inscriptions au planning" kicker="Modalités" class="lg:col-span-3">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11a4 4 0 100-8 4 4 0 000 8zm-6 10a6 6 0 0112 0m1-9l2 2 4-4" />
                    </svg>
                </x-slot>

                <div class="grid gap-3 sm:grid-cols-3">
                    <x-ui.stat label="Ouverture"
                               :value="$edition->registration_opens_at?->translatedFormat('j F Y à H\hi') ?? 'Déjà ouvertes'" />

                    <x-ui.stat label="Fermeture"
                               :value="$edition->registration_closes_at?->translatedFormat('j F Y à H\hi') ?? 'Pas de date limite'" />

                    <x-ui.stat label="Créneaux par bénévole"
                               :value="'de '.$edition->min_slots_per_volunteer.' à '.$edition->max_slots_per_volunteer" />
                </div>

                <x-slot name="footer">
                    <a href="{{ route('planning.index') }}" class="inline-flex min-h-touch items-center gap-1.5 text-sm font-bold text-primary hover:text-primary-hover">
                        Explorer la grille horaire
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </x-slot>
            </x-ui.card>
        </div>
    @endif

    <div class="grid gap-5 sm:gap-6 lg:grid-cols-3">
        @if ($edition)
            <x-ui.card title="Vos engagements" kicker="Charte du bénévole" class="lg:col-span-2">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 5h6M9 3h6a1 1 0 011 1v1h2a1 1 0 011 1v14a1 1 0 01-1 1H6a1 1 0 01-1-1V6a1 1 0 011-1h2V4a1 1 0 011-1zm0 10l2 2 4-4" />
                    </svg>
                </x-slot>

                <ul class="space-y-2.5">
                    @foreach ($commitmentRules as $rule)
                        <li class="flex items-start gap-3 rounded-xl bg-zinc-50 p-3.5 text-[0.9375rem] text-zinc-900">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gauge-free/10 text-gauge-free" aria-hidden="true">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <span>{{ $rule }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif

        <x-ui.card title="Une question ?"
                   kicker="Support & contact"
                   :subtitle="$contact ? 'L\'équipe organisatrice vous répond.' : null"
                   :class="$edition ? '' : 'lg:col-span-3'">
            <x-slot name="icon">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 21a9 9 0 100-18 9 9 0 000 18zm-2.5-11.5a2.5 2.5 0 114 2c-.9.6-1.5 1.1-1.5 2.5m0 3h.01" />
                </svg>
            </x-slot>

            @if ($contact)
                <x-ui.data-list>
                    @isset($contact['name'])
                        <x-ui.data-row label="Contact">{{ $contact['name'] }}</x-ui.data-row>
                    @endisset

                    @isset($contact['email'])
                        <x-ui.data-row label="E-mail">
                            <a class="break-all text-primary hover:text-primary-hover" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                        </x-ui.data-row>
                    @endisset

                    @isset($contact['phone'])
                        <x-ui.data-row label="Téléphone">
                            <a class="text-primary hover:text-primary-hover" href="tel:{{ preg_replace('/\s+/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>
                        </x-ui.data-row>
                    @endisset
                </x-ui.data-list>
            @else
                <x-ui.alert>
                    Les coordonnées de l'équipe organisatrice ne sont pas encore renseignées.
                </x-ui.alert>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
