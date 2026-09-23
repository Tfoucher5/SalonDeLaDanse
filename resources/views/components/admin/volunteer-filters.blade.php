{{--
    Les criteres de recherche du back-office, partages par la liste des
    benevoles et par les exports. Un seul formulaire : ce qu'on filtre a
    l'ecran est ce qu'on telecharge.

    Les criteres s'appliquent sans clic et restent dans l'URL — une recherche se
    partage et se recharge. Sans JavaScript, le bouton « Rechercher » prend le
    relais : voir `resources/js/live-filters.js`.
--}}

@props([
    'action',
    'criteria',
    'missions',
    'days',
    'target',
    'submit' => 'Rechercher',
])

<form method="GET"
      action="{{ $action }}"
      x-data="liveFilters('{{ $target }}')"
      :aria-busy="busy"
      class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.field label="Nom ou prénom" for="filtre-nom">
            {{-- La saisie libre attend une pause dans la frappe ; les listes
                 deroulantes partent des la selection. --}}
            <x-text-input id="filtre-nom" name="name" type="search"
                          :value="$criteria['name']"
                          placeholder="Doré, Camille…"
                          x-on:input.debounce.400ms="refresh()" />
        </x-ui.field>

        <x-ui.field label="Mission" for="filtre-mission" :messages="$errors->get('mission')">
            <x-ui.select x-on:change="refresh()" id="filtre-mission" name="mission">
                <option value="">Toutes les missions</option>

                @foreach ($missions as $mission)
                    <option value="{{ $mission->id }}" @selected($criteria['mission'] === $mission->id)>
                        {{ $mission->name }}{{ $mission->is_public ? '' : ' (restreinte)' }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Statut du planning" for="filtre-statut" :messages="$errors->get('status')">
            <x-ui.select x-on:change="refresh()" id="filtre-statut" name="status">
                <option value="">Tous les statuts</option>
                <option value="validated" @selected($criteria['status'] === 'validated')>Validé</option>
                <option value="pending" @selected($criteria['status'] === 'pending')>En attente</option>
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Jour" for="filtre-jour" :messages="$errors->get('day')">
            <x-ui.select x-on:change="refresh()" id="filtre-jour" name="day">
                <option value="">Tous les jours</option>

                @foreach ($days as $day)
                    <option value="{{ $day->toDateString() }}" @selected($criteria['day'] === $day->toDateString())>
                        {{ ucfirst($day->translatedFormat('l j F')) }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <x-ui.button variant="primary" type="submit" x-ref="submit">{{ $submit }}</x-ui.button>

        @if (collect($criteria)->filter()->isNotEmpty())
            <x-ui.button :href="$action" variant="ghost">Effacer les critères</x-ui.button>
        @endif

        {{ $slot }}

        {{-- Discret mais present : sans lui, une recherche lente n'a l'air de
             rien faire. --}}
        <span x-show="busy" x-cloak class="text-sm text-zinc-500">Recherche…</span>
    </div>
</form>
