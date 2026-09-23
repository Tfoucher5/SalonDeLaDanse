<x-admin-layout title="Missions">
    <x-slot name="header">
        <x-ui.page-header
            title="Missions"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Les postes du Salon, leur jauge et leur disponibilité.' : 'Aucune édition n\'est ouverte pour le moment.'">
            @if ($edition)
                <x-slot name="actions">
                    <x-ui.button :href="route('admin.missions.create')" variant="primary">
                        Nouvelle mission
                    </x-ui.button>
                </x-slot>
            @endif
        </x-ui.page-header>
    </x-slot>

    @if (session('status'))
        <x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>
    @endif

    @error('mission')
        <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
    @enderror

    @if ($edition === null)
        <x-ui.empty title="Aucune édition active">
            Les missions s'afficheront ici dès qu'une édition du Salon sera ouverte.
        </x-ui.empty>
    @elseif ($missions->isEmpty())
        <x-ui.empty title="Aucune mission pour cette édition">
            Créez la première : elle ouvrira un créneau sur chaque tranche horaire du Salon.
        </x-ui.empty>
    @else
        <x-ui.table>
            <x-slot name="head">
                <th scope="col">Mission</th>
                <th scope="col">Accès</th>
                <th scope="col">Jauge</th>
                <th scope="col">Créneaux</th>
                <th scope="col">Attribués</th>
                <th scope="col"><span class="sr-only">Modifier</span></th>
            </x-slot>

            @foreach ($missions as $mission)
                <tr class="tabular-grid">
                    <td>
                        <span class="font-medium text-zinc-900">{{ $mission->name }}</span>

                        @unless ($mission->is_active)
                            <x-ui.badge tone="full" class="ms-1 align-middle">Fermée</x-ui.badge>
                        @endunless

                        <span class="block text-sm text-zinc-500">Ordre {{ $mission->position }}</span>
                    </td>

                    <td>
                        @if ($mission->is_public)
                            <span class="text-zinc-500">Ouverte aux bénévoles</span>
                        @else
                            <x-ui.badge>Restreinte</x-ui.badge>
                        @endif
                    </td>

                    <td>{{ $mission->default_capacity }} / créneau</td>

                    <td>{{ $mission->shifts_count }}</td>

                    <td>{{ $mission->assignments_count }}</td>

                    <td class="text-right">
                        <x-ui.button :href="route('admin.missions.edit', $mission)">Modifier</x-ui.button>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.alert>
            La jauge se règle par mission et s'applique à tous ses créneaux. Pour ajuster un
            créneau en particulier, passez par la fiche du bénévole concerné.
        </x-ui.alert>
    @endif
</x-admin-layout>
