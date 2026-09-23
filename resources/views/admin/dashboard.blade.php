<x-admin-layout title="Vue d'ensemble">
    <x-slot name="header">
        <x-ui.page-header
            title="Vue d'ensemble"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Où en sont les bénévoles, et où en est le remplissage.' : 'Aucune édition n\'est ouverte pour le moment.'">
            @if ($edition)
                <x-slot name="actions">
                    <x-ui.button :href="route('admin.volunteers.index')">Chercher un bénévole</x-ui.button>
                </x-slot>
            @endif
        </x-ui.page-header>
    </x-slot>

    @if ($edition === null)
        <x-ui.empty title="Aucune édition active">
            Les compteurs s'afficheront ici dès qu'une édition du Salon sera ouverte.
        </x-ui.empty>
    @else
        {{-- Le dispositif humain : qui est attendu, qui s'est inscrit, qui a fini. --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat
                label="Bénévoles attendus"
                :value="$headcount['expected']"
                :hint="$headcount['codes_left'].' code'.($headcount['codes_left'] > 1 ? 's' : '').' encore disponible'.($headcount['codes_left'] > 1 ? 's' : '')" />

            <x-ui.stat
                label="Comptes créés"
                :value="$headcount['accounts']"
                :hint="'sur '.$headcount['expected'].' attendus'" />

            <x-ui.stat
                label="Plannings validés"
                :value="$headcount['validated']" />

            <x-ui.stat
                label="Plannings en attente"
                :value="$headcount['pending']"
                hint="comptes créés, planning non figé" />
        </div>

        <x-ui.card title="Remplissage global"
                   :subtitle="$fillRate['taken'].' place'.($fillRate['taken'] > 1 ? 's' : '').' prise'.($fillRate['taken'] > 1 ? 's' : '').' sur '.$fillRate['capacity'].' offertes, missions restreintes comprises.'">
            <x-ui.gauge
                :level="$fillRate['level']"
                :label="$fillRate['rate'].' % de remplissage'"
                :remaining="$fillRate['remaining']"
                :capacity="$fillRate['capacity']" />
        </x-ui.card>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-ui.card title="Par jour" subtitle="Le taux de remplissage de chaque journée du Salon.">
                @if ($byDay->isEmpty())
                    <x-ui.empty title="Aucun créneau en base">
                        Les créneaux se créent avec les missions et les tranches horaires de l'édition.
                    </x-ui.empty>
                @else
                    <div class="space-y-4">
                        @foreach ($byDay as $row)
                            <div>
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="font-medium text-zinc-900">{{ $row['label'] }}</p>
                                    <p class="text-sm text-zinc-500 tabular-grid">
                                        {{ $row['taken'] }} / {{ $row['capacity'] }} places
                                    </p>
                                </div>

                                <x-ui.gauge
                                    class="mt-1.5"
                                    :level="$row['level']"
                                    :label="$row['rate'].' % de remplissage'"
                                    :remaining="$row['remaining']"
                                    :capacity="$row['capacity']" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Par mission" subtitle="Les missions sous restriction sont signalées.">
                @if ($byMission->isEmpty())
                    <x-ui.empty title="Aucune mission en base">
                        Les missions de l'édition n'ont pas encore été créées.
                    </x-ui.empty>
                @else
                    <div class="space-y-4">
                        @foreach ($byMission as $row)
                            <div>
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="font-medium text-zinc-900">
                                        {{ $row['label'] }}

                                        @if ($row['restricted'])
                                            <x-ui.badge class="ms-1 align-middle">Restreinte</x-ui.badge>
                                        @endif
                                    </p>

                                    <p class="text-sm text-zinc-500 tabular-grid">
                                        {{ $row['taken'] }} / {{ $row['capacity'] }} places
                                    </p>
                                </div>

                                <x-ui.gauge
                                    class="mt-1.5"
                                    :level="$row['level']"
                                    :label="$row['rate'].' % de remplissage'"
                                    :remaining="$row['remaining']"
                                    :capacity="$row['capacity']" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    @endif
</x-admin-layout>
