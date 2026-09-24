{{--
    Ce qu'affiche le QR code d'un badge. Page publique : un agent d'accueil la
    consulte sans compte. Elle montre de quoi reconnaitre la personne et rien
    de plus — ni e-mail, ni telephone. Seul un administrateur connecte voit en
    plus son planning.

    L'etat est lu au moment du scan, et l'heure est affichee : une capture
    d'ecran ancienne se repere.
--}}

<x-guest-layout title="Vérification du badge">
    @if ($volunteer === null)
        <div class="text-center">
            <x-ui.badge tone="danger" dot>Badge non valable</x-ui.badge>

            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-zinc-900">Ce badge n'a plus de titulaire</h1>
            <p class="mt-2 text-sm text-zinc-500">
                Le compte auquel il était rattaché n'existe plus. Ne laissez pas passer sur la foi de ce badge.
            </p>
        </div>
    @else
        <div class="flex flex-col items-center text-center">
            <x-ui.avatar :user="$volunteer" size="h-28 w-28 text-3xl" />

            <p class="mt-4 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">Bénévole</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-zinc-900">{{ $volunteer->full_name }}</h1>

            @if ($edition !== null)
                <p class="mt-1 text-sm text-zinc-500">{{ $edition->name }}</p>
            @endif

            @if ($identifier !== null)
                <p class="mt-3 rounded-xl bg-zinc-100 px-3 py-1.5 font-mono text-sm font-semibold text-zinc-900 tabular-grid">{{ $identifier }}</p>
            @endif
        </div>

        @if ($isActive)
            <x-ui.alert tone="success" title="Badge valable" class="mt-6">
                {{ $volunteer->first_name }} est bénévole de cette édition.
            </x-ui.alert>
        @else
            <x-ui.alert tone="danger" title="Badge non valable" class="mt-6">
                {{ $volunteer->first_name }} n'est plus bénévole de cette édition. Ne laissez pas passer sur la foi de ce badge.
            </x-ui.alert>
        @endif

        {{-- Le controle de l'equipe, pour un administrateur connecte : ou la
             personne est attendue, et sa fiche a portee de main. --}}
        @if ($viewerIsAdmin)
            <section class="mt-6 border-t border-zinc-200 pt-5">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-plum">Contrôle équipe</p>

                    <a href="{{ route('admin.volunteers.show', $volunteer) }}" class="text-sm font-semibold text-primary hover:text-primary-hover">
                        Ouvrir la fiche
                    </a>
                </div>

                @if ($shifts->isEmpty())
                    <p class="mt-3 rounded-xl bg-zinc-50 px-3 py-2.5 text-sm text-zinc-500">Aucun créneau retenu.</p>
                @else
                    <ul class="mt-3 space-y-2">
                        @foreach ($shifts as $shift)
                            @php $isToday = $shift->date->isToday(); @endphp

                            <li @class([
                                'flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl p-3 text-sm tabular-grid',
                                'bg-primary-soft ring-1 ring-primary/30' => $isToday,
                                'bg-zinc-50' => ! $isToday,
                            ])>
                                <span class="font-bold text-zinc-900">{{ ucfirst($shift->date->translatedFormat('D j M')) }} · {{ $shift->timeSlot->label() }}</span>
                                <span class="min-w-0 flex-1 text-zinc-600">{{ $shift->mission->name }}</span>

                                @if ($isToday)
                                    <x-ui.badge tone="primary" dot>Aujourd'hui</x-ui.badge>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @elseif (auth()->guest())
            <p class="mt-5 text-center text-sm text-zinc-500">
                Membre de l'équipe ?
                <a href="{{ route('login') }}" class="font-semibold text-primary hover:text-primary-hover">Connectez-vous</a>
                pour voir son planning.
            </p>
        @endif
    @endif

    <p class="mt-6 text-center text-xs text-zinc-500 tabular-grid">
        Vérifié le {{ now()->translatedFormat('j F Y à H\hi') }}
    </p>
</x-guest-layout>
