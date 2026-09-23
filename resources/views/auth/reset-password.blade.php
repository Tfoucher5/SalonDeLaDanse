<x-guest-layout :title="__('Reset Password')" :back="route('login')" back-label="Connexion">
    <x-ui.form-heading :title="__('Reset Password')" />

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
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

        <div class="pt-1">
            <x-ui.button variant="primary" size="touch" block>{{ __('Reset Password') }}</x-ui.button>
        </div>
    </form>
</x-guest-layout>
