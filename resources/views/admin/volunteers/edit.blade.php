<x-admin-layout title="Modifier la fiche" width="md">
    <x-slot name="header">
        <x-ui.page-header
            :title="$volunteer->full_name"
            :eyebrow="$edition?->name"
            :back="route('admin.volunteers.show', $volunteer)"
            back-label="Fiche bénévole"
            subtitle="Informations personnelles verrouillées pour le bénévole, modifiables ici." />
    </x-slot>

    <x-ui.alert>
        Ces informations sont figées depuis la création du compte : le bénévole ne peut plus y
        toucher. Changer l'adresse e-mail change son identifiant de connexion, et la vérification
        devra être refaite.
    </x-ui.alert>

    <x-ui.card title="Informations personnelles">
        <form method="POST" action="{{ route('admin.volunteers.update', $volunteer) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('patch')

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field label="Prénom" for="first_name" :messages="$errors->get('first_name')" required>
                    <x-text-input id="first_name" name="first_name" type="text"
                                  :value="old('first_name', $volunteer->first_name)"
                                  required autofocus autocomplete="given-name" />
                </x-ui.field>

                <x-ui.field label="Nom" for="last_name" :messages="$errors->get('last_name')" required>
                    <x-text-input id="last_name" name="last_name" type="text"
                                  :value="old('last_name', $volunteer->last_name)"
                                  required autocomplete="family-name" />
                </x-ui.field>
            </div>

            <x-ui.field label="Adresse e-mail" for="email" :messages="$errors->get('email')" required>
                <x-text-input id="email" name="email" type="email"
                              :value="old('email', $volunteer->email)"
                              required autocomplete="username" />
            </x-ui.field>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field label="Téléphone" for="phone" :messages="$errors->get('phone')" required>
                    <x-text-input id="phone" name="phone" type="tel"
                                  :value="old('phone', $volunteer->phone)"
                                  required autocomplete="tel" />
                </x-ui.field>

                <x-ui.field label="Date de naissance" for="birth_date" :messages="$errors->get('birth_date')" required>
                    <x-text-input id="birth_date" name="birth_date" type="date"
                                  :value="old('birth_date', $volunteer->birth_date?->toDateString())"
                                  required autocomplete="bday" :max="today()->toDateString()" />
                </x-ui.field>
            </div>

            <x-ui.field label="Photo" for="photo"
                        :messages="$errors->get('photo')"
                        hint="Facultatif : la photo actuelle est conservée si vous n'en déposez pas de nouvelle.">
                <div class="flex items-center gap-4">
                    <x-ui.avatar :user="$volunteer" size="h-16 w-16" />

                    <input id="photo" name="photo" type="file"
                           accept="{{ collect(config('salon.photo.mimes'))->map(fn ($mime) => 'image/'.$mime)->implode(',') }}"
                           class="block w-full text-sm text-zinc-500 file:me-4 file:rounded-xl file:border file:border-zinc-200 file:bg-white file:px-4 file:py-2 file:text-sm file:font-medium file:text-zinc-900 hover:file:bg-zinc-100" />
                </div>
            </x-ui.field>

            <div class="flex flex-wrap items-center gap-2 pt-2">
                <x-ui.button variant="primary" size="touch" type="submit">Enregistrer</x-ui.button>

                <x-ui.button :href="route('admin.volunteers.show', $volunteer)" variant="ghost" size="touch">
                    Annuler
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
