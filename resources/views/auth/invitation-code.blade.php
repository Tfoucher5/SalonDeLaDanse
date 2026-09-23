<x-guest-layout :title="__('Enter your invitation code')">
    <h1 class="text-xl font-semibold text-zinc-900">
        {{ __('Enter your invitation code') }}
    </h1>

    <p class="mt-2 text-sm text-zinc-500">
        {{ __('The organising team sent you a code by email after your application was accepted. It can only be used once.') }}
    </p>

    <form method="POST" action="{{ route('register.code') }}" class="mt-6 space-y-4">
        @csrf

        <x-ui.field :label="__('Invitation code')" for="code" :messages="$errors->get('code')" required>
            <x-text-input id="code" type="text" name="code" :value="old('code')"
                          class="tabular-grid uppercase tracking-widest"
                          required autofocus autocomplete="off" placeholder="SALON-XXXXXX" />
        </x-ui.field>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <a class="rounded-md text-sm text-zinc-500 underline hover:text-zinc-900" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-ui.button variant="primary" size="touch">{{ __('Continue') }}</x-ui.button>
        </div>
    </form>
</x-guest-layout>
