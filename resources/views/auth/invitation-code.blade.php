<x-guest-layout>
    <h1 class="text-xl font-semibold text-zinc-900">
        {{ __('Enter your invitation code') }}
    </h1>

    <p class="mt-2 text-sm text-zinc-500">
        {{ __('The organising team sent you a code by email after your application was accepted. It can only be used once.') }}
    </p>

    <form method="POST" action="{{ route('register.code') }}" class="mt-6">
        @csrf

        <div>
            <x-input-label for="code" :value="__('Invitation code')" />
            <x-text-input id="code" class="block mt-1 w-full uppercase tracking-widest" type="text"
                          name="code" :value="old('code')" required autofocus autocomplete="off"
                          placeholder="SALON-XXXXXX" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end gap-4 mt-6">
            <a class="text-sm text-zinc-500 underline hover:text-zinc-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-ring" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button>
                {{ __('Continue') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
