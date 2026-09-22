<x-guest-layout>
    <h1 class="text-xl font-semibold text-zinc-900">
        {{ __('Create my account') }}
    </h1>

    <p class="mt-2 text-sm text-zinc-500">
        {{ __('Invitation code') }} :
        <span class="font-medium text-zinc-900 tabular-grid">{{ $code }}</span>
    </p>

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="mt-6">
        @csrf

        <!-- First name -->
        <div>
            <x-input-label for="first_name" :value="__('First Name')" />
            <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" />
            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
        </div>

        <!-- Last name -->
        <div class="mt-4">
            <x-input-label for="last_name" :value="__('Last Name')" />
            <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" />
            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Phone -->
        <div class="mt-4">
            <x-input-label for="phone" :value="__('Phone')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" max-length="10" name="phone" :value="old('phone')" required autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- Birth date -->
        <div class="mt-4">
            <x-input-label for="birth_date" :value="__('Date of birth')" />
            <x-text-input id="birth_date" class="block mt-1 w-full" type="date" name="birth_date" :value="old('birth_date')" required autocomplete="bday" max="{{ today()->toDateString() }}" />
            <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
        </div>

        <!-- Photo -->
        <div class="mt-4">
            <x-input-label for="photo" :value="__('Recent photo')" />
            <input id="photo" name="photo" type="file" required
                   accept="image/jpeg,image/png,image/webp"
                   class="mt-1 block w-full text-sm text-zinc-900 file:mr-4 file:h-10 file:rounded-md file:border file:border-zinc-200 file:bg-white file:px-4 file:text-sm file:font-medium file:text-zinc-900 hover:file:bg-zinc-100">
            <p class="mt-1 text-sm text-zinc-500">
                {{ __('JPEG, PNG or WebP.') }} {{ __('Maximum :size MB.', ['size' => round(config('salon.photo.max_kilobytes') / 1024, 1)]) }}
            </p>
            <x-input-error :messages="$errors->get('photo')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <p class="mt-6 text-sm text-zinc-500">
            {{ __('Once your account is created, only an administrator can change your name, date of birth, email address or photo.') }}
        </p>

        <div class="flex items-center justify-end gap-4 mt-4">
            <a class="text-sm text-zinc-500 underline hover:text-zinc-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-ring" href="{{ route('register.code') }}">
                {{ __('Change code') }}
            </a>

            <x-primary-button>
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
