@use('App\Enums\StaffingLevel')

<x-admin-layout title="Fiche bénévole">
    <x-slot name="header">
        <x-ui.page-header
            :title="$volunteer->full_name"
            :eyebrow="$edition?->name"
            :back="route('admin.volunteers.index')"
            back-label="Bénévoles"
            subtitle="Fiche bénévole et planning complet.">
            <x-slot name="actions">
                <div class="flex items-center gap-3 rounded-2xl bg-white px-4 py-2.5 shadow-card ring-1 ring-zinc-900/5">
                    <span class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">Planning</span>
                    <x-ui.badge :tone="$state->adminTone()" dot>{{ $state->adminLabel() }}</x-ui.badge>
                </div>

                @if ($hasBadge)
                    <x-ui.button :href="route('admin.volunteers.badge', $volunteer)" size="touch">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v7.6l2.3-2.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L9 11.6V4a1 1 0 011-1zM4 15a1 1 0 011 1v1h10v-1a1 1 0 112 0v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Badge (PDF)
                    </x-ui.button>
                @endif

                <x-ui.button :href="route('admin.volunteers.edit', $volunteer)" size="touch">
                    Modifier la fiche
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('status'))
        <x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('temporary_password'))
        {{-- Affiché une seule fois : la session flash meurt au rechargement, et
             le mot de passe n'est stocké en clair nulle part. --}}
        <x-ui.alert tone="attention" title="Mot de passe temporaire">
            <p>À transmettre au bénévole. Il disparaîtra dès que vous quitterez cette page.</p>

            <p class="mt-2 select-all rounded-xl bg-white px-3 py-2 font-mono text-base font-semibold text-zinc-900 ring-1 ring-zinc-900/5 tabular-grid">
                {{ session('temporary_password') }}
            </p>
        </x-ui.alert>
    @endif

    @error('shift_id')
        <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
    @enderror

    @error('planning')
        <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
    @enderror

    @php
        $shiftCount = $shiftsByDay->sum(fn ($shifts) => $shifts->count());
        $minimum = $edition?->min_slots_per_volunteer ?? 0;
        $maximum = $edition?->max_slots_per_volunteer ?? 0;
    @endphp

    {{-- Le planning a gauche, parce que c'est ce qu'on vient modifier ; la
         personne et les decisions qui la concernent a droite. --}}
    <div class="grid items-start gap-5 sm:gap-6 lg:grid-cols-3">
        <div class="space-y-5 sm:space-y-6 lg:col-span-2">
            <x-ui.card title="Planning" kicker="Créneaux retenus">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                    </svg>
                </x-slot>

                <x-slot name="actions">
                    <x-ui.badge :tone="StaffingLevel::forQuota($shiftCount, $minimum, $maximum)->tone()" dot class="tabular-grid">
                        {{ $shiftCount }} / {{ $maximum }} créneau{{ $maximum > 1 ? 'x' : '' }}
                    </x-ui.badge>
                </x-slot>

                @if ($shiftsByDay->isEmpty())
                    <x-ui.empty title="Ce bénévole n'a retenu aucun créneau">
                        Son planning est vide : rien ne lui a été attribué, et il n'a rien réservé.
                    </x-ui.empty>
                @else
                    <div class="space-y-5">
                        @foreach ($shiftsByDay as $shifts)
                            @php $day = $shifts->first()->date; @endphp

                            <section>
                                <h3 class="mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">
                                    {{ ucfirst($day->translatedFormat('l j F Y')) }}
                                </h3>

                                <ul class="space-y-2">
                                    @foreach ($shifts as $shift)
                                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl bg-zinc-50 p-3 tabular-grid">
                                            <span class="w-full shrink-0 font-bold text-zinc-900 sm:w-28">{{ $shift->timeSlot->label() }}</span>

                                            <span class="min-w-0 flex-1">
                                                <span class="font-semibold text-zinc-900">{{ $shift->mission->name }}</span>

                                                @unless ($shift->mission->is_public)
                                                    <x-ui.badge class="ms-1 align-middle">Restreinte</x-ui.badge>
                                                @endunless

                                                <span class="mt-1 block text-sm text-zinc-500">
                                                    @if ($shift->pivot->assigned_by_admin)
                                                        <x-ui.badge tone="plum">Équipe organisatrice</x-ui.badge>
                                                    @else
                                                        Choisie par le bénévole
                                                    @endif
                                                </span>
                                            </span>

                                            <x-ui.confirm-form
                                                :action="route('admin.volunteers.shifts.destroy', [$volunteer, $shift])"
                                                method="delete"
                                                title="Retirer ce créneau ?"
                                                confirm="Retirer le créneau"
                                                variant="danger"
                                                class="ms-auto">
                                                Retirer

                                                <x-slot name="body">
                                                    {{ $shift->mission->name }}, {{ $shift->timeSlot->label() }} le
                                                    {{ $day->translatedFormat('j F') }}, sera retiré du planning de
                                                    {{ $volunteer->full_name }}. La place redeviendra disponible.
                                                </x-slot>
                                            </x-ui.confirm-form>
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @endforeach
                    </div>
                @endif

                {{-- Attribution forcee, au pied du planning qu'elle modifie. Les
                     missions restreintes figurent dans la liste : c'est
                     precisement ce que cet ecran sert a attribuer. --}}
                <x-slot name="footer">
                    <h3 class="font-bold text-zinc-900">Attribuer un créneau</h3>
                    <p class="mt-0.5 text-sm text-zinc-500">
                        Missions restreintes comprises. Les règles de composition sont outrepassées — la règle franchie vous sera rappelée.
                    </p>

                    @if ($assignableShifts->isEmpty())
                        <p class="mt-3 rounded-xl bg-zinc-50 px-3 py-2.5 text-sm text-zinc-500">
                            Aucun créneau à attribuer : ce bénévole occupe déjà tous les créneaux de l'édition, ou l'édition n'en compte aucun.
                        </p>
                    @else
                        <form method="POST" action="{{ route('admin.volunteers.shifts.store', $volunteer) }}"
                              class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start">
                            @csrf

                            <div class="min-w-0 flex-1">
                                <label for="shift_id" class="sr-only">Créneau</label>

                                <x-ui.select id="shift_id" name="shift_id" required>
                                    <option value="">Choisir un créneau…</option>

                                    @foreach ($assignableShifts as $date => $shifts)
                                        <optgroup label="{{ ucfirst($shifts->first()->date->translatedFormat('l j F')) }}">
                                            @foreach ($shifts as $shift)
                                                <option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>
                                                    {{ $shift->timeSlot->label() }} · {{ $shift->mission->name }}{{ $shift->mission->is_public ? '' : ' (restreinte)' }} · {{ $shift->takenPlaces() }}/{{ $shift->capacity }} places
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </x-ui.select>
                            </div>

                            <x-ui.button size="touch" type="submit" class="shrink-0">Attribuer</x-ui.button>
                        </form>
                    @endif
                </x-slot>
            </x-ui.card>
        </div>

        <div class="space-y-5 sm:space-y-6">
            <x-ui.card>
                <div class="flex items-center gap-4">
                    <x-ui.avatar :user="$volunteer" size="h-16 w-16 text-lg" />

                    <div class="min-w-0">
                        <p class="truncate text-lg font-bold text-zinc-900">{{ $volunteer->full_name }}</p>
                        <p class="text-sm text-zinc-500">{{ $volunteer->role->label() }}</p>
                    </div>
                </div>

                <x-ui.data-list class="mt-5">
                    <x-ui.data-row label="E-mail">
                        <a class="break-all text-primary hover:text-primary-hover" href="mailto:{{ $volunteer->email }}">{{ $volunteer->email }}</a>
                    </x-ui.data-row>

                    <x-ui.data-row label="Téléphone">
                        @if (filled($volunteer->phone))
                            <a class="text-primary hover:text-primary-hover" href="tel:{{ preg_replace('/\s+/', '', $volunteer->phone) }}">{{ $volunteer->phone }}</a>
                        @else
                            <span class="text-zinc-500">Non renseigné</span>
                        @endif
                    </x-ui.data-row>

                    <x-ui.data-row label="Compte créé le">
                        {{ $volunteer->created_at?->translatedFormat('j F Y à H\hi') }}
                    </x-ui.data-row>
                </x-ui.data-list>
            </x-ui.card>

            {{-- La validation definitive, et son contraire : les deux se
                 decident ici. Le benevole compose, l'organisation fige. --}}
            <x-ui.card title="Validation du planning">
                @if ($volunteer->planningIsValidated())
                    <x-ui.alert tone="success">
                        Validé le {{ $volunteer->planning_validated_at->translatedFormat('j F Y à H\hi') }}.
                        Le bénévole ne peut plus le modifier.
                    </x-ui.alert>

                    <x-ui.confirm-form
                        :action="route('admin.volunteers.unlock', $volunteer)"
                        title="Rouvrir ce planning ?"
                        confirm="Rouvrir le planning"
                        size="touch"
                        class="mt-4 [&>button]:w-full">
                        Rouvrir le planning

                        <x-slot name="body">
                            La validation définitive sera levée et {{ $volunteer->first_name }} pourra de
                            nouveau composer son planning, si la fenêtre d'inscription est ouverte. Une
                            attribution de l'équipe organisatrice, elle, continuerait de le verrouiller.
                        </x-slot>
                    </x-ui.confirm-form>
                @else
                    <p class="text-sm text-zinc-500">{{ $state->description() }}</p>

                    @if ($shiftCount < $minimum)
                        <x-ui.alert tone="attention" class="mt-4">
                            {{ $shiftCount }} créneau{{ $shiftCount > 1 ? 'x' : '' }} sur les
                            {{ $minimum }} attendus au minimum. Attribuez-en un avant de valider.
                        </x-ui.alert>
                    @endif

                    <x-ui.confirm-form
                        :action="route('admin.volunteers.validate', $volunteer)"
                        title="Valider définitivement ce planning ?"
                        confirm="Valider définitivement"
                        variant="primary"
                        size="touch"
                        class="mt-4 [&>button]:w-full">
                        Valider définitivement

                        <x-slot name="body">
                            Le planning de {{ $volunteer->full_name }} sera figé et passera en lecture seule.
                            Vous pourrez toujours le rouvrir ou le modifier depuis cette fiche.
                        </x-slot>
                    </x-ui.confirm-form>
                @endif
            </x-ui.card>

            <x-ui.card title="Accès au compte"
                       subtitle="Aucun e-mail n'est envoyé : le mot de passe temporaire s'affiche sur cette page, à vous de le transmettre.">
                <x-ui.confirm-form
                    :action="route('admin.volunteers.credentials', $volunteer)"
                    title="Réinitialiser le mot de passe ?"
                    confirm="Réinitialiser"
                    variant="danger"
                    size="touch"
                    class="[&>button]:w-full">
                    Réinitialiser le mot de passe

                    <x-slot name="body">
                        Le mot de passe actuel de {{ $volunteer->full_name }} cessera immédiatement de
                        fonctionner, et ses sessions ouvertes seront fermées. Le nouveau mot de passe
                        s'affichera une seule fois.
                    </x-slot>
                </x-ui.confirm-form>
            </x-ui.card>
        </div>
    </div>

    {{-- Sur mobile, la validation est l'action qu'on vient faire : elle flotte
         au-dessus de la barre d'onglets plutot que d'attendre en bas de page.
         Sur grand ecran, la carte « Validation du planning » est deja visible. --}}
    @unless ($volunteer->planningIsValidated())
        <div class="h-20 lg:hidden" aria-hidden="true"></div>

        <div class="fixed inset-x-4 bottom-[calc(5.25rem+env(safe-area-inset-bottom))] z-50 md:bottom-6 lg:hidden print-hidden">
            <x-ui.confirm-form
                :action="route('admin.volunteers.validate', $volunteer)"
                title="Valider définitivement ce planning ?"
                confirm="Valider définitivement"
                variant="primary"
                size="touch"
                class="mx-auto max-w-md [&>button]:w-full">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" />
                </svg>
                Valider définitivement

                <x-slot name="body">
                    Le planning de {{ $volunteer->full_name }} sera figé et passera en lecture seule.
                    Vous pourrez toujours le rouvrir ou le modifier depuis cette fiche.
                </x-slot>
            </x-ui.confirm-form>
        </div>
    @endunless
</x-admin-layout>
