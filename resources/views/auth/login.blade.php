<x-guest-layout :title="__('Log in')">
    <h1 class="text-xl font-semibold text-zinc-900">{{ __('Log in') }}</h1>

    <p class="mt-2 text-sm text-zinc-500">
        Retrouvez votre planning et vos créneaux.
    </p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <x-ui.field :label="__('Email')" for="email" :messages="$errors->get('email')">
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        </x-ui.field>

        <x-ui.field :label="__('Password')" for="password" :messages="$errors->get('password')">
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
        </x-ui.field>

        <label for="remember_me" class="inline-flex items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember"
                   class="rounded-md border-zinc-200 text-primary focus:ring-primary-ring">
            <span class="text-sm text-zinc-500">{{ __('Remember me') }}</span>
        </label>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            @if (Route::has('password.request'))
                <a class="rounded-md text-sm text-zinc-500 underline hover:text-zinc-900" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-ui.button variant="primary" size="touch">{{ __('Log in') }}</x-ui.button>
        </div>
    </form>
</x-guest-layout>
