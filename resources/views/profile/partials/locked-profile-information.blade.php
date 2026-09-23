<x-ui.card :title="__('Profile Information')"
           :subtitle="__('These details are locked. Contact the organising team to have them changed.')">
    <x-ui.data-list>
        <x-ui.data-row :label="__('First Name')">{{ $user->first_name }}</x-ui.data-row>
        <x-ui.data-row :label="__('Last Name')">{{ $user->last_name }}</x-ui.data-row>
        <x-ui.data-row :label="__('Email')">{{ $user->email }}</x-ui.data-row>
        <x-ui.data-row :label="__('Phone')">{{ $user->phone }}</x-ui.data-row>

        <x-ui.data-row :label="__('Recent photo')">
            @if ($user->photo_path)
                <x-ui.avatar :user="$user" size="h-16 w-16" />
            @else
                <span class="text-zinc-400">{{ __('None') }}</span>
            @endif
        </x-ui.data-row>
    </x-ui.data-list>
</x-ui.card>
