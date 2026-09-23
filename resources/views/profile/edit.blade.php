<x-app-layout :title="__('Profile')" width="md">
    <x-slot name="header">
        <x-ui.page-header
            :title="__('Profile')"
            subtitle="Vos informations et votre mot de passe." />
    </x-slot>

    @can('updatePersonalInformation', $user)
        @include('profile.partials.update-profile-information-form')
    @else
        @include('profile.partials.locked-profile-information')
    @endcan

    @include('profile.partials.update-password-form')
</x-app-layout>
