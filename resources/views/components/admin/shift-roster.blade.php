{{--
    Un creneau vu par l'organisation : sa jauge, et surtout la liste nominative
    de qui l'occupe.

    C'est le pendant exact de `x-planning.shift-card`, qui ne montre au benevole
    qu'un nombre de places. Ici on nomme, et on distingue ce que l'equipe a pose
    de ce que le benevole a choisi.
--}}

@use('App\Enums\GaugeLevel')

@props(['shift'])

@php
    $volunteers = $shift->volunteers->sortBy(fn ($volunteer) => $volunteer->last_name.' '.$volunteer->first_name);
    $taken = $volunteers->count();
    $remaining = max(0, $shift->capacity - $taken);
    $level = GaugeLevel::fromRemaining($remaining, $shift->capacity);
@endphp

<article {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white p-4']) }}>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <h3 class="min-w-0 font-medium text-zinc-900">
            {{ $shift->mission->name }}

            @unless ($shift->mission->is_public)
                <x-ui.badge class="ms-1 align-middle">Restreinte</x-ui.badge>
            @endunless
        </h3>

        <p class="tabular-grid text-sm text-zinc-500">{{ $taken }} / {{ $shift->capacity }}</p>
    </div>

    <x-ui.gauge
        class="mt-2"
        :level="$level->value"
        :label="$level->label($remaining)"
        :remaining="$remaining"
        :capacity="$shift->capacity" />

    @if ($volunteers->isEmpty())
        <p class="mt-3 text-sm text-zinc-500">Personne n'est inscrit sur ce créneau.</p>
    @else
        <ul class="mt-3 space-y-1.5">
            @foreach ($volunteers as $volunteer)
                <li>
                    <a href="{{ route('admin.volunteers.show', $volunteer) }}"
                       class="flex items-center gap-2 rounded-md px-1.5 py-1 text-sm text-zinc-900 transition hover:bg-zinc-100">
                        <x-ui.avatar :user="$volunteer" size="h-6 w-6" />

                        <span class="min-w-0 truncate">{{ $volunteer->full_name }}</span>

                        @if ($volunteer->pivot->assigned_by_admin)
                            <x-ui.badge tone="primary" class="ms-auto shrink-0" title="Attribué par l'équipe organisatrice">
                                Équipe
                            </x-ui.badge>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</article>
