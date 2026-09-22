<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold text-zinc-900">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-zinc-200 rounded-lg">
                <div class="p-6 text-zinc-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
