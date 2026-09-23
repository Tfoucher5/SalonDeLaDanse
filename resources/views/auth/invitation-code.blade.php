<x-guest-layout :title="__('Enter your invitation code')" :back="url('/')" back-label="Accueil">
    <x-ui.form-heading
        step="Étape 1 sur 4"
        :title="__('Enter your invitation code')"
        :subtitle="__('The organising team sent you a code by email after your application was accepted. It can only be used once.')" />

    <form method="POST" action="{{ route('register.code') }}" class="space-y-4">
        @csrf

        <x-ui.field :label="__('Invitation code')" for="code" :messages="$errors->get('code')" required>
            <x-text-input id="code" type="text" name="code" :value="old('code')"
                          class="tabular-grid text-center text-lg font-bold uppercase tracking-widest"
                          required autofocus autocomplete="off" placeholder="SALON-XXXXXX" />
        </x-ui.field>

        <x-ui.button variant="primary" size="touch" block>{{ __('Continue') }}</x-ui.button>
    </form>

    <p class="mt-5 border-t border-zinc-200 pt-4 text-center text-sm text-zinc-500">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" class="font-semibold text-primary hover:text-primary-hover">{{ __('Log in') }}</a>
    </p>
</x-guest-layout>
