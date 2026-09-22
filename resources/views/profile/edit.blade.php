<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold text-zinc-900">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white border border-zinc-200 rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @can('updatePersonalInformation', $user)
                <div class="p-4 sm:p-8 bg-white border border-zinc-200 rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            @else
                <div class="p-4 sm:p-8 bg-white border border-zinc-200 rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.locked-profile-information')
                    </div>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
