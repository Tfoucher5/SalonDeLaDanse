@use('App\Enums\GaugeLevel')

{{--
    Carte de creneau : le composant le plus manipule de la plateforme.

    Un etat se lit en niveaux de gris — les places restantes sont ecrites en
    toutes lettres, la couleur ne fait que confirmer. Quand une regle empeche la
    reservation, le motif est affiche en clair : « indisponible » n'apprend rien
    au benevole.

    Alpine ne sert qu'au retour visuel pendant l'aller-retour serveur : le bouton
    se desactive et s'annonce, l'etat reel revient avec la page rechargee.
--}}

@props([
    'shift',
    'booked' => false,
    'motive' => null,
    'editable' => false,
])

@php
    $gauge = GaugeLevel::for($shift);
    $isBlocked = ! $booked && $motive !== null;

    $surface = match (true) {
        $booked => 'bg-primary-soft/60 ring-2 ring-primary',
        $isBlocked => 'bg-zinc-50 ring-1 ring-zinc-900/5',
        default => 'bg-white shadow-card ring-1 ring-zinc-900/5 hover:-translate-y-0.5 hover:shadow-lift',
    };
@endphp

<article {{ $attributes->merge(['class' => 'flex flex-col rounded-2xl p-4 transition duration-200 tabular-grid sm:p-5 '.$surface]) }}>
    <div class="flex items-start justify-between gap-3">
        <h3 class="min-w-0 text-base font-bold leading-snug tracking-tight {{ $isBlocked ? 'text-zinc-400' : 'text-zinc-900' }}">
            {{ $shift->mission->name }}
        </h3>

        @if ($booked)
            <x-ui.badge tone="primary-outline" class="shrink-0">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" />
                </svg>
                Réservé
            </x-ui.badge>
        @else
            <x-ui.badge :tone="$gauge->value" dot class="shrink-0">
                {{ $gauge->label($shift->remaining_places) }}
            </x-ui.badge>
        @endif
    </div>

    @if ($shift->mission->instructions)
        <p class="mt-1.5 line-clamp-2 text-sm {{ $isBlocked ? 'text-zinc-400' : 'text-zinc-500' }}">{{ $shift->mission->instructions }}</p>
    @endif

    @if ($booked)
        <p @class([
            'mt-1.5 text-sm font-semibold',
            'text-gauge-free' => $gauge === GaugeLevel::Free,
            'text-gauge-tight' => $gauge === GaugeLevel::Tight,
            'text-gauge-full' => $gauge === GaugeLevel::Full,
        ])>{{ $gauge->label($shift->remaining_places) }}</p>
    @endif

    @if ($isBlocked)
        <p class="mt-3 flex items-start gap-2 rounded-xl bg-zinc-100 px-3 py-2 text-sm text-zinc-600">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 4a1 1 0 11-2 0 1 1 0 012 0zm-2 3a1 1 0 012 0v5a1 1 0 11-2 0V9z" clip-rule="evenodd" />
            </svg>
            <span>{{ $motive }}</span>
        </p>
    @endif

    <div class="mt-auto flex items-center justify-between gap-3 pt-4">
        <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">{{ $shift->timeSlot->label() }}</p>

        @if ($editable && $booked)
            <form method="POST"
                  action="{{ route('planning.shifts.destroy', $shift) }}"
                  x-data="{ pending: false }"
                  @submit="pending = true">
                @csrf
                @method('DELETE')

                <x-ui.button variant="danger" size="touch" x-bind:disabled="pending">
                    <span x-show="! pending">Retirer ce créneau</span>
                    <span x-show="pending" x-cloak>Enregistrement…</span>
                </x-ui.button>
            </form>
        @elseif ($editable && ! $isBlocked)
            <form method="POST"
                  action="{{ route('planning.shifts.store', $shift) }}"
                  x-data="{ pending: false }"
                  @submit="pending = true">
                @csrf

                <x-ui.button variant="primary" size="touch" x-bind:disabled="pending">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" x-show="! pending">
                        <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 5a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                    </svg>
                    <span x-show="! pending">Réserver</span>
                    <span x-show="pending" x-cloak>Enregistrement…</span>
                </x-ui.button>
            </form>
        @endif
    </div>
</article>
