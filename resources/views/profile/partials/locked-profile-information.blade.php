<x-ui.card :title="__('Profile Information')">
    <x-slot name="actions">
        <x-ui.badge>
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v2H5a1 1 0 00-1 1v8a1 1 0 001 1h10a1 1 0 001-1V9a1 1 0 00-1-1h-1V6a4 4 0 00-4-4zm2 6V6a2 2 0 10-4 0v2h4z" clip-rule="evenodd" />
            </svg>
            Lecture seule
        </x-ui.badge>
    </x-slot>

    <x-ui.alert>
        {{ __('These details are locked. Contact the organising team to have them changed.') }}
    </x-ui.alert>

    {{-- Carte d'identite : la photo, le nom et le role. --}}
    <div class="mt-5 flex items-center gap-4 rounded-2xl bg-zinc-50 p-4">
        <x-ui.avatar :user="$user" size="h-16 w-16 text-lg sm:h-20 sm:w-20" />

        <div class="min-w-0">
            <p class="truncate text-lg font-bold tracking-tight text-zinc-900 sm:text-xl">{{ $user->full_name }}</p>

            <div class="mt-1 flex flex-wrap items-center gap-2">
                <x-ui.badge tone="primary">{{ $user->role->label() }}</x-ui.badge>

                @if ($user->email_verified_at)
                    <span class="inline-flex items-center gap-1 text-sm text-gauge-free">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm4 5.7l-5 5a1 1 0 01-1.4 0l-2.3-2.3a1 1 0 011.4-1.4l1.6 1.6 4.3-4.3a1 1 0 011.4 1.4z" clip-rule="evenodd" />
                        </svg>
                        E-mail validé
                    </span>
                @endif
            </div>
        </div>
    </div>

    <dl class="mt-4 grid gap-3 sm:grid-cols-2">
        <x-ui.readonly-field :label="__('First Name')">{{ $user->first_name }}</x-ui.readonly-field>
        <x-ui.readonly-field :label="__('Last Name')">{{ $user->last_name }}</x-ui.readonly-field>
        <x-ui.readonly-field :label="__('Email')">{{ $user->email }}</x-ui.readonly-field>
        <x-ui.readonly-field :label="__('Phone')">{{ $user->phone }}</x-ui.readonly-field>

        <x-ui.readonly-field :label="__('Date of birth')">
            @if ($user->birth_date)
                {{ $user->birth_date->translatedFormat('d/m/Y') }}
            @else
                <span class="italic text-zinc-400">{{ __('None') }}</span>
            @endif
        </x-ui.readonly-field>

        <x-ui.readonly-field :label="__('Recent photo')">
            @if ($user->photo_path)
                Fournie
            @else
                <span class="italic text-zinc-400">{{ __('None') }}</span>
            @endif
        </x-ui.readonly-field>
    </dl>
</x-ui.card>
