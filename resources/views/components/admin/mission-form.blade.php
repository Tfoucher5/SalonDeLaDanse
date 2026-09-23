{{--
    Le formulaire d'une mission, partage par la creation et la modification.
    Les deux ecrans posent exactement les memes questions ; seule l'action
    change, et la modification affiche en plus ce qui est deja attribue.
--}}

@props([
    'action',
    'method' => 'post',
    'mission' => null,
    'submit' => 'Enregistrer',
    'position' => 1,
])

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @unless ($method === 'post')
        @method($method)
    @endunless

    <x-ui.field label="Nom de la mission" for="name" :messages="$errors->get('name')" required>
        <x-text-input id="name" name="name" type="text"
                      :value="old('name', $mission?->name)"
                      required autofocus maxlength="120" />
    </x-ui.field>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field label="Nombre de personnes par créneau"
                    for="default_capacity"
                    :messages="$errors->get('default_capacity')"
                    hint="La jauge s'applique à chaque créneau de la mission, sur tous les jours du Salon."
                    required>
            <x-text-input id="default_capacity" name="default_capacity" type="number"
                          :value="old('default_capacity', $mission?->default_capacity ?? config('salon.seed.default_shift_capacity'))"
                          required min="1" max="200" inputmode="numeric" />
        </x-ui.field>

        <x-ui.field label="Ordre d'affichage" for="position"
                    :messages="$errors->get('position')"
                    hint="Détermine la place de la mission dans la grille."
                    required>
            <x-text-input id="position" name="position" type="number"
                          :value="old('position', $mission?->position ?? $position)"
                          required min="1" max="99" inputmode="numeric" />
        </x-ui.field>
    </div>

    <x-ui.field label="Consignes" for="instructions"
                :messages="$errors->get('instructions')"
                hint="Affichées sur la fiche récapitulative du bénévole. Facultatives.">
        <textarea id="instructions" name="instructions" rows="4" maxlength="2000"
                  class="w-full rounded-md border-zinc-200 text-sm text-zinc-900 focus:border-primary focus:ring-2 focus:ring-primary-ring">{{ old('instructions', $mission?->instructions) }}</textarea>
    </x-ui.field>

    {{-- Les deux interrupteurs de la mission. Ils ne disent pas la meme chose :
         l'un dit qui peut reserver, l'autre si la mission existe encore. --}}
    <fieldset class="space-y-3 rounded-lg border border-zinc-200 p-4">
        <legend class="px-1 text-sm font-medium text-zinc-500">Disponibilité</legend>

        <label class="flex items-start gap-3" for="is_public">
            <input type="hidden" name="is_public" value="0">
            <input id="is_public" name="is_public" type="checkbox" value="1"
                   @checked(old('is_public', $mission?->is_public ?? true))
                   class="mt-0.5 h-5 w-5 rounded border-zinc-200 text-primary focus:ring-2 focus:ring-primary-ring">

            <span class="min-w-0">
                <span class="block font-medium text-zinc-900">Réservable par les bénévoles</span>
                <span class="block text-sm text-zinc-500">
                    Décochez pour une mission sous restriction, comme Billetterie ou Caisse :
                    elle n'apparaît plus dans la grille et ne s'attribue que depuis une fiche bénévole.
                </span>
            </span>
        </label>

        <label class="flex items-start gap-3" for="is_active">
            <input type="hidden" name="is_active" value="0">
            <input id="is_active" name="is_active" type="checkbox" value="1"
                   @checked(old('is_active', $mission?->is_active ?? true))
                   class="mt-0.5 h-5 w-5 rounded border-zinc-200 text-primary focus:ring-2 focus:ring-primary-ring">

            <span class="min-w-0">
                <span class="block font-medium text-zinc-900">Mission active</span>
                <span class="block text-sm text-zinc-500">
                    Décochez pour fermer la mission : plus personne ne peut la réserver, mais
                    les bénévoles déjà inscrits gardent leur poste.
                </span>
            </span>
        </label>
    </fieldset>

    <div class="flex flex-wrap items-center gap-2 pt-2">
        <x-ui.button variant="primary" size="touch" type="submit">{{ $submit }}</x-ui.button>

        <x-ui.button :href="route('admin.missions.index')" variant="ghost" size="touch">Annuler</x-ui.button>
    </div>
</form>
