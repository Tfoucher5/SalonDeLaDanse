<x-guest-layout :title="__('Resend Verification Email')">
    <h1 class="text-xl font-semibold text-zinc-900">
        {{ __('Your email address is unverified.') }}
    </h1>

    <p class="mt-2 text-sm text-zinc-500">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <x-ui.alert tone="success" class="mt-6">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </x-ui.alert>
    @endif

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <x-ui.button variant="ghost" size="touch">{{ __('Log Out') }}</x-ui.button>
        </form>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-ui.button variant="primary" size="touch">{{ __('Resend Verification Email') }}</x-ui.button>
        </form>
    </div>
</x-guest-layout>
