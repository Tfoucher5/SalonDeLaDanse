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

        <div class="flex items-start justify-between gap-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('Date of birth') }}</dt>
            <dd class="text-sm text-zinc-900 tabular-grid">
                {{ $user->birth_date?->translatedFormat('d/m/Y') ?? __('None') }}
            </dd>
        </div>

        <div class="flex items-start justify-between gap-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('Recent photo') }}</dt>
            <dd>
                @if ($user->photo_path)
                    <img src="{{ Storage::disk('public')->url($user->photo_path) }}"
                         alt="" class="h-16 w-16 rounded-md border border-zinc-200 object-cover">
                @else
                    <span class="text-sm text-zinc-400">{{ __('None') }}</span>
                @endif
            </dd>
        </div>
    </dl>
</section>
