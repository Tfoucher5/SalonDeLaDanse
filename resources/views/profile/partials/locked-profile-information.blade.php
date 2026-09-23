<section>
    <header>
        <h2 class="text-lg font-semibold text-zinc-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-zinc-500">
            {{ __('These details are locked. Contact the organising team to have them changed.') }}
        </p>
    </header>

    <dl class="mt-6 divide-y divide-zinc-200 border-t border-zinc-200">
        <div class="flex items-start justify-between gap-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('First Name') }}</dt>
            <dd class="text-sm text-zinc-900">{{ $user->first_name }}</dd>
        </div>

        <div class="flex items-start justify-between gap-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('Last Name') }}</dt>
            <dd class="text-sm text-zinc-900">{{ $user->last_name }}</dd>
        </div>

        <div class="flex items-start justify-between gap-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('Email') }}</dt>
            <dd class="text-sm text-zinc-900">{{ $user->email }}</dd>
        </div>

        <div class="flex items-start justify-between gap-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('Phone') }}</dt>
            <dd class="text-sm text-zinc-900 tabular-grid">{{ $user->phone }}</dd>
        </div>

        <x-ui.data-row :label="__('Recent photo')">
            @if ($user->photo_path)
                <x-ui.avatar :user="$user" size="h-16 w-16" />
            @else
                <span class="text-zinc-400">{{ __('None') }}</span>
            @endif
        </x-ui.data-row>
    </x-ui.data-list>
</x-ui.card>
