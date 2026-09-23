<x-admin-layout title="Nouvelle mission" width="md">
    <x-slot name="header">
        <x-ui.page-header
            title="Nouvelle mission"
            :eyebrow="$edition->name"
            subtitle="Elle recevra un créneau sur chaque tranche horaire, tous les jours du Salon.">
            <x-slot name="actions">
                <x-ui.button :href="route('admin.missions.index')" variant="ghost">
                    Retour aux missions
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card title="La mission">
        <x-admin.mission-form
            :action="route('admin.missions.store')"
            :position="$nextPosition"
            submit="Créer la mission" />
    </x-ui.card>
</x-admin-layout>
