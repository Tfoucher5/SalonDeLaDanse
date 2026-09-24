<x-admin-layout title="Badges">
    <x-slot name="header">
        <x-ui.page-header
            title="Badges"
            :eyebrow="$edition?->name"
            :subtitle="$edition ? 'Badges prêts à imprimer : photo, nom, identifiant et QR code de vérification, quatre par page A4.' : 'Aucune édition n\'est ouverte pour le moment.'">
            <x-slot name="actions">
                <x-ui.button type="button" size="touch" x-data x-on:click="$dispatch('open-modal', 'badge-scanner')">
                    Scanner un badge
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if ($edition === null)
        <x-ui.empty title="Aucune édition active">
            Les badges s'imprimeront ici dès qu'une édition du Salon sera ouverte.
        </x-ui.empty>
    @else
        @error('mode')
            <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
        @enderror

        @error('volunteers')
            <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
        @enderror

        {{-- Avant d'imprimer : un badge sans photo porte les initiales, et se
             repere mal a l'entree. La liste ignore les filtres, elle porte sur
             toute l'edition. --}}
        @if ($withoutPhoto->isNotEmpty())
            <x-ui.alert tone="attention" :title="$withoutPhoto->count().' bénévole'.($withoutPhoto->count() > 1 ? 's' : '').' sans photo'">
                <p>Leur badge portera leurs initiales à la place de la photo. Ajoutez-la depuis leur fiche avant d'imprimer.</p>

                <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                    @foreach ($withoutPhoto as $volunteer)
                        <li>
                            <a href="{{ route('admin.volunteers.edit', $volunteer) }}" class="font-semibold text-primary hover:text-primary-hover">
                                {{ $volunteer->full_name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        {{-- La selection vit sur cet element, hors de la zone que le filtrage
             sans clic remplace : voir `resources/js/badge-selection.js`. --}}
        <div x-data="badgeSelection" class="space-y-5 sm:space-y-6">
            <x-ui.card>
                <x-admin.volunteer-filters
                    :action="route('admin.badges.index')"
                    :criteria="$criteria"
                    :missions="$missions"
                    :days="$days"
                    target="resultats-badges" />
            </x-ui.card>

            {{-- Le formulaire de generation. Ses champs sont repartis : la
                 selection memorisee ici, les criteres et les cases dans la zone
                 rejouee, rattaches par l'attribut `form`. --}}
            <form id="badges-form" method="POST" action="{{ route('admin.badges.download') }}" class="hidden">
                @csrf

                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="volunteers[]" x-bind:value="id">
                </template>
            </form>

            <x-ui.card title="Sélection manuelle" subtitle="Cochez des bénévoles dans la liste : la sélection survit aux changements de critères.">
                <x-slot name="actions">
                    <x-ui.badge tone="primary" class="tabular-grid">
                        {{-- Un seul enfant : le `gap` de la capsule separerait
                             sinon le « s » du pluriel. --}}
                        <span><span x-text="selected.length">0</span> coché<span x-show="selected.length > 1">s</span></span>
                    </x-ui.badge>
                </x-slot>

                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.button type="button" variant="ghost" x-on:click="checkAll()">Tout cocher</x-ui.button>
                    <x-ui.button type="button" variant="ghost" x-on:click="uncheckAll()" x-bind:disabled="selected.length === 0">Tout décocher</x-ui.button>

                    <x-ui.button type="submit" form="badges-form" name="mode" value="selection"
                                 class="sm:ms-auto" x-bind:disabled="selected.length === 0">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v7.6l2.3-2.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L9 11.6V4a1 1 0 011-1zM4 15a1 1 0 011 1v1h10v-1a1 1 0 112 0v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Imprimer la sélection
                    </x-ui.button>
                </div>
            </x-ui.card>

            {{-- Zone rejouee par le filtrage sans clic. Son identifiant est le
                 contrat avec `liveFilters`. --}}
            <div id="resultats-badges" class="space-y-4" aria-live="polite">
                {{-- Dans la zone rejouee : le mode « filtres » envoie ainsi les
                     criteres saisis, sans rechargement. --}}
                @foreach ($criteria as $key => $value)
                    @if (filled($value))
                        <input type="hidden" form="badges-form" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="tabular-grid text-sm font-semibold text-zinc-500">
                        {{ $volunteers->count() }} bénévole{{ $volunteers->count() > 1 ? 's' : '' }}
                        {{ collect($criteria)->filter()->isNotEmpty() ? 'pour cette recherche' : 'inscrits' }}
                    </p>

                    @if ($volunteers->isNotEmpty())
                        <x-ui.button variant="primary" type="submit" form="badges-form" name="mode" value="filters">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v7.6l2.3-2.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L9 11.6V4a1 1 0 011-1zM4 15a1 1 0 011 1v1h10v-1a1 1 0 112 0v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Imprimer {{ $volunteers->count() > 1 ? 'les '.$volunteers->count().' badges' : 'le badge' }} (PDF)
                        </x-ui.button>
                    @endif
                </div>

                @if ($volunteers->isEmpty())
                    <x-ui.empty title="Aucun bénévole ne correspond à cette recherche">
                        Élargissez les critères, ou vérifiez l'orthographe du nom.
                    </x-ui.empty>
                @else
                    @php $withoutPhotoIds = $withoutPhoto->modelKeys(); @endphp

                    <ul class="divide-y divide-zinc-200 overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-zinc-900/5">
                        @foreach ($volunteers as $volunteer)
                            <li>
                                <label for="badge-{{ $volunteer->id }}"
                                       class="flex cursor-pointer items-center gap-3 px-4 py-3 tabular-grid transition hover:bg-zinc-50 has-[:checked]:bg-primary-soft/40">
                                    {{-- Nommee, la case fonctionne aussi sans JavaScript ;
                                         le doublon avec la selection memorisee est
                                         ecarte cote serveur. --}}
                                    <input id="badge-{{ $volunteer->id }}" type="checkbox"
                                           name="volunteers[]" value="{{ $volunteer->id }}" form="badges-form"
                                           data-badge-volunteer
                                           x-bind:checked="isSelected({{ $volunteer->id }})"
                                           x-on:change="toggle({{ $volunteer->id }}, $event.target.checked)"
                                           class="h-5 w-5 shrink-0 rounded border-zinc-200 text-primary focus:ring-2 focus:ring-primary-ring">

                                    <x-ui.avatar :user="$volunteer" size="h-10 w-10" />

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-semibold text-zinc-900">{{ $volunteer->full_name }}</span>
                                        <span class="block text-sm text-zinc-500">{{ $identifiers[$volunteer->id] }}</span>
                                    </span>

                                    @if (in_array($volunteer->id, $withoutPhotoIds, true))
                                        <x-ui.badge tone="tight" dot>Sans photo</x-ui.badge>
                                    @endif
                                </label>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif
</x-admin-layout>
