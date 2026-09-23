<x-admin-layout title="Nouvelle mission" width="md">
    <x-slot name="header">
        <x-ui.page-header
            title="Nouvelle mission"
            :eyebrow="$edition->name"
            :back="route('admin.missions.index')"
            back-label="Missions"
            subtitle="Elle recevra un créneau sur chaque tranche horaire, tous les jours du Salon." />
    </x-slot>

    <x-ui.card title="La mission">
        <x-admin.mission-form
            :action="route('admin.missions.store')"
            :position="$nextPosition"
            submit="Créer la mission" />
    </x-ui.card>
</x-admin-layout>
