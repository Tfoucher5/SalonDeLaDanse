<x-admin-layout title="Modifier la mission" width="md">
    <x-slot name="header">
        <x-ui.page-header
            :title="$mission->name"
            :eyebrow="$edition?->name"
            subtitle="Jauge, consignes et disponibilité de la mission.">
            <x-slot name="actions">
                <x-ui.badge :tone="$mission->is_active ? 'free' : 'full'">
                    {{ $mission->is_active ? 'Active' : 'Fermée' }}
                </x-ui.badge>

                <x-ui.button :href="route('admin.missions.index')" variant="ghost">
                    Retour aux missions
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @error('mission')
        <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
    @enderror

    {{-- Ce que l'administrateur doit savoir avant de toucher a la jauge ou de
         fermer la mission : combien de personnes sont deja dessus. --}}
    @if ($assigned > 0)
        <x-ui.alert tone="attention" title="Cette mission est déjà pourvue">
            {{ $assigned }} créneau{{ $assigned > 1 ? 'x' : '' }}
            y {{ $assigned > 1 ? 'sont' : 'est' }} attribué{{ $assigned > 1 ? 's' : '' }}.
            Baisser la jauge en dessous du nombre de personnes déjà inscrites sur un créneau
            sera refusé, et la mission ne peut plus être supprimée — seulement fermée.
        </x-ui.alert>
    @endif

    <x-ui.card title="La mission">
        <x-admin.mission-form
            :action="route('admin.missions.update', $mission)"
            method="patch"
            :mission="$mission"
            submit="Enregistrer" />
    </x-ui.card>

    <x-ui.card title="Supprimer"
               subtitle="La mission et tous ses créneaux disparaissent. Impossible si des bénévoles y sont inscrits.">
        <x-ui.confirm-form
            :action="route('admin.missions.destroy', $mission)"
            method="delete"
            title="Supprimer cette mission ?"
            confirm="Supprimer la mission"
            variant="danger">
            Supprimer la mission

            <x-slot name="body">
                « {{ $mission->name }} » et l'ensemble de ses créneaux seront supprimés
                définitivement. Pour la retirer de la grille sans rien perdre, fermez-la plutôt.
            </x-slot>
        </x-ui.confirm-form>
    </x-ui.card>
</x-admin-layout>
