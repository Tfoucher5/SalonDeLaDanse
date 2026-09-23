<x-guest-layout :title="__('Log in')" :back="url('/')" back-label="Accueil">
    <x-ui.form-heading :title="__('Log in')" subtitle="Retrouvez votre planning et vos créneaux." />

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-ui.field :label="__('Email')" for="email" :messages="$errors->get('email')">
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        </x-ui.field>

        <x-ui.field :label="__('Password')" for="password" :messages="$errors->get('password')">
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
        </x-ui.field>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex min-h-touch items-center gap-2.5">
                <input id="remember_me" type="checkbox" name="remember"
                       class="h-5 w-5 rounded-md border-zinc-900/20 text-primary focus:ring-primary-bright/30">
                <span class="text-sm text-zinc-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="inline-flex min-h-touch items-center text-sm font-semibold text-primary hover:text-primary-hover" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <x-ui.button variant="primary" size="touch" block>{{ __('Log in') }}</x-ui.button>
    </form>

    <p class="mt-5 border-t border-zinc-200 pt-4 text-center text-sm text-zinc-500">
        Vous avez reçu un code d'invitation ?
        <a href="{{ route('register.code') }}" class="font-semibold text-primary hover:text-primary-hover">Créer mon compte</a>
    </p>
</x-guest-layout>
