<x-admin-layout title="Fiche bénévole" width="lg">
    <x-slot name="header">
        <x-ui.page-header
            :title="$volunteer->full_name"
            :eyebrow="$edition?->name"
            subtitle="Fiche bénévole et planning complet.">
            <x-slot name="actions">
                <x-ui.badge :tone="$state->tone()">{{ $state->label() }}</x-ui.badge>

                <x-ui.button :href="route('admin.volunteers.edit', $volunteer)">
                    Modifier la fiche
                </x-ui.button>

                <x-ui.button :href="route('admin.volunteers.index')" variant="ghost">
                    Retour à la liste
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

            <p class="mt-2 select-all rounded-xl bg-zinc-50 px-3 py-2 ring-1 ring-zinc-900/5 font-medium text-zinc-900 tabular-grid">
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

    <x-ui.card title="Informations">
        <div class="flex items-start gap-4">
            <x-ui.avatar :user="$volunteer" size="h-16 w-16" />

            <x-ui.data-list class="min-w-0 flex-1">
                <x-ui.data-row label="Nom">{{ $volunteer->full_name }}</x-ui.data-row>

                <x-ui.data-row label="E-mail">
                    <a class="text-primary hover:text-primary-hover" href="mailto:{{ $volunteer->email }}">{{ $volunteer->email }}</a>
                </x-ui.data-row>

                <x-ui.data-row label="Téléphone">
                    @if (filled($volunteer->phone))
                        <a class="text-primary hover:text-primary-hover" href="tel:{{ preg_replace('/\s+/', '', $volunteer->phone) }}">{{ $volunteer->phone }}</a>
                    @else
                        <span class="text-zinc-500">Non renseigné</span>
                    @endif
                </x-ui.data-row>

                <x-ui.data-row label="Rôle">{{ $volunteer->role->label() }}</x-ui.data-row>

                <x-ui.data-row label="Compte créé le">
                    {{ $volunteer->created_at?->translatedFormat('j F Y à H\hi') }}
                </x-ui.data-row>

                <x-ui.data-row label="Planning validé le">
                    {{ $volunteer->planning_validated_at?->translatedFormat('j F Y à H\hi') ?? 'Pas encore validé' }}
                </x-ui.data-row>
            </x-ui.data-list>
        </div>

        <x-ui.alert class="mt-4">{{ $state->description() }}</x-ui.alert>
    </x-ui.card>

    {{-- La validation definitive : c'est ici qu'elle se decide, et nulle part
         ailleurs. Le benevole compose, l'organisation fige. --}}
    <x-ui.card title="Validation du planning"
               :subtitle="$volunteer->planningIsValidated()
                    ? 'Ce planning est figé. Le bénévole ne peut plus le modifier.'
                    : 'Figer ce planning le passe en lecture seule pour le bénévole.'">
        @if ($volunteer->planningIsValidated())
            <x-ui.alert tone="success">
                Validé le {{ $volunteer->planning_validated_at->translatedFormat('j F Y à H\hi') }}.
                Utilisez « Rouvrir le planning » ci-dessous pour rendre la main au bénévole.
            </x-ui.alert>
        @else
            @php
                $shiftCount = $shiftsByDay->sum(fn ($shifts) => $shifts->count());
                $minimum = $edition?->min_slots_per_volunteer ?? 0;
            @endphp

            @if ($shiftCount < $minimum)
                <x-ui.alert tone="attention">
                    Ce planning compte {{ $shiftCount }} créneau{{ $shiftCount > 1 ? 'x' : '' }} sur les
                    {{ $minimum }} attendus au minimum. Attribuez-lui un créneau ci-dessous avant de valider.
                </x-ui.alert>
            @endif

            <div class="mt-4">
                <x-ui.confirm-form
                    :action="route('admin.volunteers.validate', $volunteer)"
                    title="Valider définitivement ce planning ?"
                    confirm="Valider définitivement"
                    variant="primary"
                    size="touch">
                    Valider définitivement

                    <x-slot name="body">
                        Le planning de {{ $volunteer->full_name }} sera figé et passera en lecture seule.
                        Vous pourrez toujours le rouvrir ou le modifier depuis cette fiche.
                    </x-slot>
                </x-ui.confirm-form>
            </div>
        @endif
    </x-ui.card>

    {{-- Les deux leviers qui touchent au compte lui-meme, pas au planning. --}}
    <x-ui.card title="Accès au compte"
               subtitle="Le MVP n'envoie aucun e-mail : le mot de passe temporaire s'affiche ici, à vous de le transmettre.">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.confirm-form
                :action="route('admin.volunteers.credentials', $volunteer)"
                title="Réinitialiser le mot de passe ?"
                confirm="Réinitialiser"
                variant="danger">
                Réinitialiser le mot de passe

                <x-slot name="body">
                    Le mot de passe actuel de {{ $volunteer->full_name }} cessera immédiatement de
                    fonctionner, et ses sessions ouvertes seront fermées. Le nouveau mot de passe
                    s'affichera une seule fois.
                </x-slot>
            </x-ui.confirm-form>

            @if ($volunteer->planningIsValidated())
                <x-ui.confirm-form
                    :action="route('admin.volunteers.unlock', $volunteer)"
                    title="Rouvrir ce planning ?"
                    confirm="Rouvrir le planning">
                    Rouvrir le planning

                    <x-slot name="body">
                        La validation définitive sera levée et {{ $volunteer->first_name }} pourra de
                        nouveau composer son planning, si la fenêtre d'inscription est ouverte. Une
                        attribution de l'équipe organisatrice, elle, continuerait de le verrouiller.
                    </x-slot>
                </x-ui.confirm-form>
            @endif
        </div>
    </x-ui.card>

    {{-- Attribution forcee. Les missions restreintes figurent dans la liste :
         c'est precisement ce que cet ecran sert a attribuer. --}}
    <x-ui.card title="Attribuer un créneau"
               subtitle="Missions restreintes comprises. Les règles de composition sont outrepassées — la règle franchie vous sera rappelée.">
        @if ($assignableShifts->isEmpty())
            <x-ui.empty title="Aucun créneau à attribuer">
                Ce bénévole occupe déjà tous les créneaux de l'édition, ou l'édition n'en compte aucun.
            </x-ui.empty>
        @else
            <form method="POST" action="{{ route('admin.volunteers.shifts.store', $volunteer) }}"
                  class="flex flex-wrap items-end gap-3">
                @csrf

                <x-ui.field label="Créneau" for="shift_id" :messages="$errors->get('shift_id')" class="min-w-0 flex-1">
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
                </x-ui.field>

                <x-ui.button size="touch" type="submit">Attribuer</x-ui.button>
            </form>
        @endif
    </x-ui.card>

    @if ($shiftsByDay->isEmpty())
        <x-ui.empty title="Ce bénévole n'a retenu aucun créneau">
            Son planning est vide : rien ne lui a été attribué, et il n'a rien réservé.
        </x-ui.empty>
    @else
        @foreach ($shiftsByDay as $shifts)
            @php $day = $shifts->first()->date; @endphp

            <x-ui.card :title="ucfirst($day->translatedFormat('l j F Y'))"
                       :subtitle="$shifts->count().' créneau'.($shifts->count() > 1 ? 'x' : '')">
                <x-ui.table>
                    <x-slot name="head">
                        <th scope="col">Tranche horaire</th>
                        <th scope="col">Mission</th>
                        <th scope="col">Attribution</th>
                        <th scope="col"><span class="sr-only">Retirer</span></th>
                    </x-slot>

                    @foreach ($shifts as $shift)
                        <tr class="tabular-grid">
                            <td>{{ $shift->timeSlot->label() }}</td>

                            <td>
                                <span class="font-medium text-zinc-900">{{ $shift->mission->name }}</span>

                                @unless ($shift->mission->is_public)
                                    <x-ui.badge class="ms-1 align-middle">Restreinte</x-ui.badge>
                                @endunless
                            </td>

                            <td>
                                @if ($shift->pivot->assigned_by_admin)
                                    <x-ui.badge tone="primary">Équipe organisatrice</x-ui.badge>
                                @else
                                    <span class="text-zinc-500">Choisie par le bénévole</span>
                                @endif
                            </td>

                            <td class="text-right">
                                <x-ui.confirm-form
                                    :action="route('admin.volunteers.shifts.destroy', [$volunteer, $shift])"
                                    method="delete"
                                    title="Retirer ce créneau ?"
                                    confirm="Retirer le créneau"
                                    variant="danger"
                                    class="inline-block">
                                    Retirer

                                    <x-slot name="body">
                                        {{ $shift->mission->name }}, {{ $shift->timeSlot->label() }} le
                                        {{ $day->translatedFormat('j F') }}, sera retiré du planning de
                                        {{ $volunteer->full_name }}. La place redeviendra disponible.
                                    </x-slot>
                                </x-ui.confirm-form>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        @endforeach
    @endif
</x-admin-layout>
