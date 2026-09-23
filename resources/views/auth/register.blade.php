@php
    // Le formulaire tient en trois ecrans pour ne jamais defiler sur un
    // telephone. Apres un refus du serveur, on repart toujours de la premiere
    // etape : un navigateur ne reremplit jamais un champ fichier, la photo est
    // donc a choisir de nouveau.
    $initialStep = 1;

    $photoHint = __('JPEG, PNG or WebP.').' '.__('Maximum :size MB.', ['size' => round(config('salon.photo.max_kilobytes') / 1024, 1)]);
@endphp

<x-guest-layout :title="__('Create my account')" :back="route('register.code')" back-label="Code">
    <div x-data="{
            step: {{ $initialStep }},
            total: 3,
            preview: null,
            fileName: null,
            invalidField(step) {
                return [...this.$refs['step' + step].querySelectorAll('input')].find((field) => ! field.checkValidity());
            },
            next() {
                const field = this.invalidField(this.step);
                field ? field.reportValidity() : this.step++;
            },
            submit(event) {
                for (let step = 1; step <= this.total; step++) {
                    const field = this.invalidField(step);
                    if (field) {
                        event.preventDefault();
                        this.step = step;
                        this.$nextTick(() => field.reportValidity());
                        return;
                    }
                }
            },
         }">
        <x-ui.form-heading :title="__('Create my account')">
            <div class="mt-3 flex items-center justify-between gap-3">
                <p class="whitespace-nowrap text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">
                    Étape <span x-text="step + 1">{{ $initialStep + 1 }}</span> sur 4
                </p>

                <p class="inline-flex items-center whitespace-nowrap rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs">
                    <span class="sr-only">{{ __('Invitation code') }} :</span>
                    <span class="tabular-grid font-bold text-zinc-900">{{ $code }}</span>
                </p>
            </div>

            <div class="mt-2 flex gap-1.5" aria-hidden="true">
                @for ($segment = 1; $segment <= 4; $segment++)
                    <span class="h-1.5 flex-1 rounded-full transition-colors duration-300"
                          x-bind:class="{{ $segment }} <= step + 1 ? 'bg-primary' : 'bg-zinc-200'"></span>
                @endfor
            </div>
        </x-ui.form-heading>

        {{-- `novalidate` : la validation native ne sait pas ouvrir une etape
             masquee, le composant s'en charge avant l'envoi. --}}
        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data"
              novalidate x-on:submit="submit($event)">
            @csrf

            @if ($errors->any())
                <x-ui.alert tone="attention" class="mb-4">
                    Certains champs sont à corriger. Par sécurité, choisissez de nouveau votre photo.
                </x-ui.alert>
            @endif

            {{-- La photo d'abord, avec son apercu (elle figurera sur le badge),
                 puis le nom. --}}
            <div x-ref="step1" x-show="step === 1" @if ($initialStep !== 1) x-cloak @endif class="space-y-4">
                <x-ui.field :label="__('Recent photo')" for="photo" :messages="$errors->get('photo')" required>
                    <label for="photo"
                           class="flex cursor-pointer items-center gap-4 rounded-2xl border-2 border-dashed border-zinc-300 bg-zinc-50 p-3 transition hover:border-primary-bright focus-within:border-primary-bright focus-within:ring-4 focus-within:ring-primary-bright/15">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white shadow-card ring-2 ring-white">
                            <img x-show="preview" x-bind:src="preview" alt="Aperçu de votre photo" class="h-full w-full object-cover" x-cloak>
                            <svg x-show="! preview" class="h-7 w-7 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 8a2 2 0 012-2h1.5l1.2-1.8A1 1 0 019.5 4h5a1 1 0 01.8.4L16.5 6H18a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V8z" />
                                <path d="M12 16a3.5 3.5 0 100-7 3.5 3.5 0 000 7z" />
                            </svg>
                        </span>

                        <span class="min-w-0">
                            <span class="block font-semibold text-zinc-900" x-text="preview ? 'Changer de photo' : 'Choisir une photo'">Choisir une photo</span>
                            <span class="block truncate text-xs text-zinc-500" x-text="fileName ?? @js($photoHint)">{{ $photoHint }}</span>
                        </span>

                        <input id="photo" name="photo" type="file" required
                               accept="image/jpeg,image/png,image/webp"
                               class="sr-only"
                               x-on:change="
                                   const file = $event.target.files[0];
                                   if (preview) { URL.revokeObjectURL(preview); }
                                   preview = file ? URL.createObjectURL(file) : null;
                                   fileName = file ? file.name : null;
                               ">
                    </label>
                </x-ui.field>

                <x-ui.field :label="__('First Name')" for="first_name" :messages="$errors->get('first_name')" required>
                    <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" required autocomplete="given-name" />
                </x-ui.field>

                <x-ui.field :label="__('Last Name')" for="last_name" :messages="$errors->get('last_name')" required>
                    <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" />
                </x-ui.field>
            </div>

            {{-- Les coordonnees. --}}
            <div x-ref="step2" x-show="step === 2" @if ($initialStep !== 2) x-cloak @endif class="space-y-4">
                <x-ui.field :label="__('Email')" for="email" :messages="$errors->get('email')" required>
                    <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
                </x-ui.field>

                <x-ui.field :label="__('Phone')" for="phone" :messages="$errors->get('phone')" required>
                    <x-text-input id="phone" type="tel" name="phone" :value="old('phone')" required autocomplete="tel" />
                </x-ui.field>

                <x-ui.field :label="__('Date of birth')" for="birth_date" :messages="$errors->get('birth_date')" required>
                    <x-text-input id="birth_date" type="date" name="birth_date" :value="old('birth_date')" required autocomplete="bday" max="{{ today()->toDateString() }}" />
                </x-ui.field>
            </div>

            {{-- Le mot de passe, et ce qui deviendra non modifiable. --}}
            <div x-ref="step3" x-show="step === 3" @if ($initialStep !== 3) x-cloak @endif class="space-y-4">
                <x-ui.field :label="__('Password')" for="password" :messages="$errors->get('password')" required>
                    <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                </x-ui.field>

                <x-ui.field :label="__('Confirm Password')" for="password_confirmation" :messages="$errors->get('password_confirmation')" required>
                    <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                </x-ui.field>

                <x-ui.alert>
                    {{ __('Once your account is created, only an administrator can change your name, date of birth, email address or photo.') }}
                </x-ui.alert>
            </div>

            <div class="mt-5 flex gap-3">
                <x-ui.button type="button" size="touch" class="flex-1" x-show="step > 1" x-cloak x-on:click="step--">
                    Précédent
                </x-ui.button>

                <x-ui.button type="button" variant="primary" size="touch" class="flex-1" x-show="step < total" x-on:click="next()">
                    Continuer
                </x-ui.button>

                <x-ui.button variant="primary" size="touch" class="flex-1" x-show="step === total" x-cloak>
                    {{ __('Register') }}
                </x-ui.button>
            </div>
        </form>
    </div>
</x-guest-layout>
