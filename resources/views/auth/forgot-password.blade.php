<x-guest-layout :title="__('Forgot your password?')">
    <h1 class="text-xl font-semibold text-zinc-900">{{ __('Forgot your password?') }}</h1>

    <p class="mt-2 text-sm text-zinc-500">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <x-ui.field :label="__('Email')" for="email" :messages="$errors->get('email')" required>
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
        </x-ui.field>

        <div class="flex justify-end pt-2">
            <x-ui.button variant="primary" size="touch">{{ __('Email Password Reset Link') }}</x-ui.button>
        </div>
    </form>
</x-guest-layout>
