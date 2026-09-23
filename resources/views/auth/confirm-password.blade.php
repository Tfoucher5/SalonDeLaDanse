<x-guest-layout :title="__('Confirm')">
    <h1 class="text-xl font-semibold text-zinc-900">{{ __('Confirm Password') }}</h1>

    <p class="mt-2 text-sm text-zinc-500">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <x-ui.field :label="__('Password')" for="password" :messages="$errors->get('password')" required>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
        </x-ui.field>

        <div class="flex justify-end pt-2">
            <x-ui.button variant="primary" size="touch">{{ __('Confirm') }}</x-ui.button>
        </div>
    </form>
</x-guest-layout>
