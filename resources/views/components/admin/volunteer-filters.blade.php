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
    {{-- Sur telephone, seul le nom reste visible : les trois listes se
         deplient a la demande, pour que les resultats arrivent sans
         defiler un ecran entier. Deja ouvertes si l'une d'elles filtre. --}}
    @php $refinements = collect($criteria)->only(['mission', 'status', 'day'])->filter()->count(); @endphp

    <div x-data="{ more: {{ $refinements > 0 ? 'true' : 'false' }} }" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.field label="Nom ou prénom" for="filtre-nom">
            {{-- La saisie libre attend une pause dans la frappe ; les listes
                 deroulantes partent des la selection. --}}
            <x-text-input id="filtre-nom" name="name" type="search"
                          :value="$criteria['name']"
                          placeholder="Doré, Camille…"
                          x-on:input.debounce.400ms="refresh()" />
        </x-ui.field>

        <button type="button" x-cloak x-on:click="more = ! more"
                class="flex h-11 items-center justify-between rounded-xl bg-zinc-100 px-4 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-200/70 sm:hidden"
                x-bind:aria-expanded="more">
            <span>
                Plus de filtres
                @if ($refinements > 0)
                    <span class="ms-1 rounded-full bg-primary px-2 py-0.5 text-xs text-white">{{ $refinements }}</span>
                @endif
            </span>

            <svg class="h-4 w-4 text-zinc-500 transition" x-bind:class="more && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z" clip-rule="evenodd" />
            </svg>
        </button>

        <x-ui.field label="Mission" for="filtre-mission" :messages="$errors->get('mission')" x-bind:class="! more && 'max-sm:hidden'">
            <x-ui.select x-on:change="refresh()" id="filtre-mission" name="mission">
                <option value="">Toutes les missions</option>

                @foreach ($missions as $mission)
                    <option value="{{ $mission->id }}" @selected($criteria['mission'] === $mission->id)>
                        {{ $mission->name }}{{ $mission->is_public ? '' : ' (restreinte)' }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Statut du planning" for="filtre-statut" :messages="$errors->get('status')" x-bind:class="! more && 'max-sm:hidden'">
            <x-ui.select x-on:change="refresh()" id="filtre-statut" name="status">
                <option value="">Tous les statuts</option>
                <option value="validated" @selected($criteria['status'] === 'validated')>Validé</option>
                <option value="pending" @selected($criteria['status'] === 'pending')>Non validé</option>
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Jour" for="filtre-jour" :messages="$errors->get('day')" x-bind:class="! more && 'max-sm:hidden'">
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
