{{--
    Un creneau vu par l'organisation : la tuile a jauge du benevole
    (`x-planning.shift-card`), mais nominative et lue a l'envers. Le benevole
    se rejouit d'une place libre ; l'equipe y voit un poste a pourvoir. Les
    couleurs suivent donc `StaffingLevel` : rouge a pourvoir, ambre en cours,
    vert complet.

    Chaque nom porte l'etat de son planning : vert pastel s'il est valide,
    rouge pastel s'il attend encore la validation.
--}}

@use('App\Enums\GaugeLevel')
@use('App\Enums\StaffingLevel')

@props(['shift'])

@php
    $volunteers = $shift->volunteers->sortBy(fn ($volunteer) => $volunteer->last_name.' '.$volunteer->first_name);
    $taken = $volunteers->count();
    $remaining = max(0, $shift->capacity - $taken);
    $staffing = StaffingLevel::fromCounts($taken, $shift->capacity);
@endphp

<article {{ $attributes->merge(['class' => 'flex flex-col rounded-2xl bg-white p-5 shadow-card tabular-grid '.($staffing === StaffingLevel::Staffed ? 'ring-2 ring-gauge-free/40' : 'ring-1 ring-zinc-900/5')]) }}>
    <div class="mb-2 flex items-center justify-between gap-2">
        <span class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-400">{{ $taken }} / {{ $shift->capacity }} inscrits</span>

        <x-ui.badge :tone="$staffing->tone()" :dot="$staffing !== StaffingLevel::Staffed" class="shrink-0">
            {{ GaugeLevel::fromRemaining($remaining, $shift->capacity)->label($remaining) }}
        </x-ui.badge>
    </div>

    {{-- Comme chez le benevole, la jauge part pleine et se vide a chaque
         inscription : un segment par place encore libre, dans la couleur du
         pourvoi. Un creneau complet n'a plus de segment colore. --}}
    <x-ui.segment-gauge :remaining="$remaining" :capacity="$shift->capacity" :level="$staffing->tone()"
                        :label="$remaining.' place'.($remaining > 1 ? 's' : '').' libre'.($remaining > 1 ? 's' : '').' sur '.$shift->capacity" />

    <h3 class="pt-3 text-lg font-bold leading-snug tracking-tight text-zinc-900">
        {{ $shift->mission->name }}

        @unless ($shift->mission->is_public)
            <x-ui.badge class="ms-1 align-middle">Restreinte</x-ui.badge>
        @endunless
    </h3>

    @if ($volunteers->isEmpty())
        <p class="mt-3 rounded-xl bg-danger/5 px-3 py-2.5 text-sm text-zinc-500">Personne n'est inscrit sur ce créneau.</p>
    @else
        <ul class="mt-2 space-y-1">
            @foreach ($volunteers as $volunteer)
                @php $validated = $volunteer->planningIsValidated(); @endphp

                <li>
                    <a href="{{ route('admin.volunteers.show', $volunteer) }}"
                       title="{{ $validated ? 'Planning validé' : 'Planning non validé' }}"
                       @class([
                           'flex min-h-[2.5rem] items-center gap-2.5 rounded-xl px-2 py-1 text-sm text-zinc-900 ring-1 ring-inset transition',
                           'bg-gauge-free/10 ring-gauge-free/15 hover:bg-gauge-free/15' => $validated,
                           'bg-danger/5 ring-danger/15 hover:bg-danger/10' => ! $validated,
                       ])>
                        <x-ui.avatar :user="$volunteer" size="h-7 w-7" />

                        <span class="min-w-0 truncate font-medium">{{ $volunteer->full_name }}</span>

                        <span class="ms-auto flex shrink-0 items-center gap-1.5">
                            @if ($volunteer->pivot->assigned_by_admin)
                                <x-ui.badge tone="plum" title="Attribué par l'équipe organisatrice">Équipe</x-ui.badge>
                            @endif

                            @if ($validated)
                                <svg class="h-4 w-4 text-gauge-free" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="h-4 w-4 text-danger/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3a1 1 0 102 0V7zm-1 7a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            @endif
                            <span class="sr-only">{{ $validated ? 'Planning validé' : 'Planning non validé' }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</article>
