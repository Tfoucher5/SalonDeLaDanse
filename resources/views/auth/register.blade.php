<x-guest-layout :title="__('Create my account')" width="sm:max-w-lg">
    <h1 class="text-xl font-semibold text-zinc-900">
        {{ __('Create my account') }}
    </h1>

    <p class="mt-2 text-sm text-zinc-500">
        {{ __('Invitation code') }} :
        <span class="tabular-grid font-medium text-zinc-900">{{ $code }}</span>
    </p>

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.field :label="__('First Name')" for="first_name" :messages="$errors->get('first_name')" required>
                <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" />
            </x-ui.field>

            <x-ui.field :label="__('Last Name')" for="last_name" :messages="$errors->get('last_name')" required>
                <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" />
            </x-ui.field>
        </div>

        <x-ui.field :label="__('Email')" for="email" :messages="$errors->get('email')" required>
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
        </x-ui.field>

        <x-ui.field :label="__('Phone')" for="phone" :messages="$errors->get('phone')" required>
            <x-text-input id="phone" type="tel" name="phone" :value="old('phone')" required autocomplete="tel" />
        </x-ui.field>

        <x-ui.field :label="__('Recent photo')" for="photo" :messages="$errors->get('photo')" required
                    :hint="__('JPEG, PNG or WebP.').' '.__('Maximum :size MB.', ['size' => round(config('salon.photo.max_kilobytes') / 1024, 1)])">
            <input id="photo" name="photo" type="file" required
                   accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm text-zinc-900 file:mr-4 file:h-10 file:rounded-md file:border file:border-zinc-200 file:bg-white file:px-4 file:text-sm file:font-medium file:text-zinc-900 hover:file:bg-zinc-100">
        </x-ui.field>

        <x-ui.field :label="__('Password')" for="password" :messages="$errors->get('password')" required>
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
        </x-ui.field>

        <x-ui.field :label="__('Confirm Password')" for="password_confirmation" :messages="$errors->get('password_confirmation')" required>
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
        </x-ui.field>

        <x-ui.alert class="mt-6">
            {{ __('Once your account is created, only an administrator can change your name, email address or photo.') }}
        </x-ui.alert>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <a class="rounded-md text-sm text-zinc-500 underline hover:text-zinc-900" href="{{ route('register.code') }}">
                {{ __('Change code') }}
            </a>

            <x-ui.button variant="primary" size="touch">{{ __('Register') }}</x-ui.button>
        </div>
    </form>
</x-guest-layout>
