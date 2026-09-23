<x-guest-layout :title="__('Forgot your password?')" :back="route('login')" back-label="Connexion">
    <x-ui.form-heading
        :title="__('Forgot your password?')"
        :subtitle="__('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.')" />

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-ui.field :label="__('Email')" for="email" :messages="$errors->get('email')" required>
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
        </x-ui.field>

        <x-ui.button variant="primary" size="touch" block>{{ __('Email Password Reset Link') }}</x-ui.button>
    </form>
</x-guest-layout>
