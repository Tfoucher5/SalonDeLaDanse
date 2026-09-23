<x-guest-layout :title="__('Reset Password')">
    <h1 class="text-xl font-semibold text-zinc-900">{{ __('Reset Password') }}</h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-ui.field :label="__('Email')" for="email" :messages="$errors->get('email')" required>
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
        </x-ui.field>

        <x-ui.field :label="__('Password')" for="password" :messages="$errors->get('password')" required>
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
        </x-ui.field>

        <x-ui.field :label="__('Confirm Password')" for="password_confirmation" :messages="$errors->get('password_confirmation')" required>
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
        </x-ui.field>

        <div class="flex justify-end pt-2">
            <x-ui.button variant="primary" size="touch">{{ __('Reset Password') }}</x-ui.button>
        </div>
    </form>
</x-guest-layout>
