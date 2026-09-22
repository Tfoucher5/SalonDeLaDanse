@use('App\Enums\PlanningState')

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold text-zinc-900">Bonjour {{ $user->first_name }}</h2>
        <p class="mt-1 text-sm text-zinc-500">
            @if ($edition)
                {{ $edition->name }}
            @else
                Aucune édition n'est ouverte pour le moment.
            @endif
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- État du planning : la première chose à savoir en arrivant. --}}
            <section class="bg-white border border-zinc-200 rounded-lg p-6">
                <div class="flex flex-wrap items-center gap-3">
                    <h3 class="text-lg font-semibold text-zinc-900">Mon planning</h3>

                    @php
                        $badge = match ($state) {
                            PlanningState::Draft => 'bg-primary-soft text-primary',
                            PlanningState::Validated => 'border border-zinc-200 text-gauge-free',
                            PlanningState::Closed => 'bg-zinc-100 text-gauge-full',
                        };
                    @endphp

                    <span class="inline-flex items-center h-7 px-3 rounded-md text-sm font-medium {{ $badge }}">
                        {{ $state->label() }}
                    </span>
                </div>

                <p class="mt-3 text-zinc-900">{{ $state->description() }}</p>

                <a href="{{ route('planning.index') }}"
                   class="mt-5 inline-flex h-10 items-center rounded-md bg-primary px-4 font-medium text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary-ring">
                    Voir le planning
                </a>

                @unless ($state->isEditable())
                    <p class="mt-4 flex items-start gap-2 rounded-md bg-zinc-100 p-4 text-sm text-zinc-500">
                        <span aria-hidden="true">&#9432;</span>
                        <span>Le planning est en <strong class="font-medium text-zinc-900">consultation seule</strong> : vous pouvez le lire, mais aucune modification n'est possible.</span>
                    </p>
                @endunless
            </section>

            @if ($edition)
                <div class="grid gap-6 md:grid-cols-2">

                    {{-- Dates du Salon --}}
                    <section class="bg-white border border-zinc-200 rounded-lg p-6 tabular-grid">
                        <h3 class="text-lg font-semibold text-zinc-900">Dates du Salon</h3>

                        <dl class="mt-4 space-y-3 text-sm">
                            @foreach ($edition->days() as $day)
                                <div class="flex justify-between gap-4 border-b border-zinc-200 pb-3 last:border-0 last:pb-0">
                                    <dt class="text-zinc-500">{{ ucfirst($day->translatedFormat('l')) }}</dt>
                                    <dd class="text-zinc-900">{{ $day->translatedFormat('j F Y') }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    {{-- Fenêtre d'inscription et quotas --}}
                    <section class="bg-white border border-zinc-200 rounded-lg p-6 tabular-grid">
                        <h3 class="text-lg font-semibold text-zinc-900">Inscriptions au planning</h3>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4 border-b border-zinc-200 pb-3">
                                <dt class="text-zinc-500">Ouverture</dt>
                                <dd class="text-zinc-900">
                                    {{ $edition->registration_opens_at?->translatedFormat('j F Y à H\hi') ?? 'Déjà ouvertes' }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4 border-b border-zinc-200 pb-3">
                                <dt class="text-zinc-500">Fermeture</dt>
                                <dd class="text-zinc-900">
                                    {{ $edition->registration_closes_at?->translatedFormat('j F Y à H\hi') ?? 'Pas de date limite' }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Créneaux par bénévole</dt>
                                <dd class="text-zinc-900">
                                    de {{ $edition->min_slots_per_volunteer }} à {{ $edition->max_slots_per_volunteer }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>

                {{-- Règles d'engagement --}}
                <section class="bg-white border border-zinc-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-zinc-900">Vos engagements</h3>

                    <ul class="mt-4 space-y-3 text-sm text-zinc-900">
                        @foreach ($commitmentRules as $rule)
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-md bg-zinc-400" aria-hidden="true"></span>
                                <span>{{ $rule }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Équipe organisatrice --}}
            <section class="bg-white border border-zinc-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-zinc-900">Une question ?</h3>

                @if ($contact)
                    <p class="mt-1 text-sm text-zinc-500">L'équipe organisatrice vous répond.</p>

                    <dl class="mt-4 space-y-3 text-sm">
                        @isset($contact['name'])
                            <div class="flex flex-wrap justify-between gap-1">
                                <dt class="text-zinc-500">Contact</dt>
                                <dd class="text-zinc-900">{{ $contact['name'] }}</dd>
                            </div>
                        @endisset
                        @isset($contact['email'])
                            <div class="flex flex-wrap justify-between gap-1">
                                <dt class="text-zinc-500">E-mail</dt>
                                <dd>
                                    <a class="text-primary hover:text-primary-hover" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                                </dd>
                            </div>
                        @endisset
                        @isset($contact['phone'])
                            <div class="flex flex-wrap justify-between gap-1 tabular-grid">
                                <dt class="text-zinc-500">Téléphone</dt>
                                <dd>
                                    <a class="text-primary hover:text-primary-hover" href="tel:{{ preg_replace('/\s+/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>
                                </dd>
                            </div>
                        @endisset
                    </dl>
                @else
                    <p class="mt-1 text-sm text-zinc-500">
                        Les coordonnées de l'équipe organisatrice ne sont pas encore renseignées.
                    </p>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
