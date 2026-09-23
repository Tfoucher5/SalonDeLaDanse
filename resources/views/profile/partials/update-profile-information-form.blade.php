<x-ui.card :title="__('Profile Information')"
           :subtitle="__('Update your account\'s profile information and email address.')">
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.field :label="__('First Name')" for="first_name" :messages="$errors->get('first_name')" required>
                <x-text-input id="first_name" name="first_name" type="text" :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
            </x-ui.field>

            <x-ui.field :label="__('Last Name')" for="last_name" :messages="$errors->get('last_name')" required>
                <x-text-input id="last_name" name="last_name" type="text" :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
            </x-ui.field>
        </div>

        <x-ui.field :label="__('Phone')" for="phone" :messages="$errors->get('phone')" required>
            <x-text-input id="phone" name="phone" type="tel" :value="old('phone', $user->phone)" required autocomplete="tel" />
        </x-ui.field>
        <x-ui.field :label="__('Date of birth')" for="birth_date" :messages="$errors->get('birth_date')" required>
            <x-text-input id="birth_date" name="birth_date" type="date" :value="old('birth_date', $user->birth_date?->toDateString())" required autocomplete="bday" max="{{ today()->toDateString() }}" />
        </x-ui.field>

        <x-ui.field :label="__('Email')" for="email" :messages="$errors->get('email')" required>
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
        </x-ui.field>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <x-ui.alert tone="attention">
                {{ __('Your email address is unverified.') }}

                <button form="send-verification" class="rounded-md text-sm text-zinc-500 underline hover:text-zinc-900">
                    {{ __('Click here to re-send the verification email.') }}
                </button>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-sm font-medium text-gauge-free">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            </x-ui.alert>
        @endif

        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:justify-end">
            <x-ui.button variant="primary" size="touch" class="w-full sm:w-auto">{{ __('Save') }}</x-ui.button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gauge-free">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</x-ui.card>
