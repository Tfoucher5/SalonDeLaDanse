<x-app-layout :title="__('Profile')">
    <x-slot name="header">
        <x-ui.page-header
            :title="__('Profile')"
            :back="route('dashboard')"
            back-label="Tableau de bord"
            eyebrow="Espace bénévole"
            subtitle="Vos informations personnelles et l'état de votre participation." />
    </x-slot>

    <div class="grid items-start gap-5 sm:gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @can('updatePersonalInformation', $user)
                @include('profile.partials.update-profile-information-form')
            @else
                @include('profile.partials.locked-profile-information')
            @endcan
        </div>

        <div class="space-y-5 sm:space-y-6">
            {{-- Recapitulatif de participation : l'etat du planning, a portee
                 depuis le profil sans repasser par le tableau de bord. --}}
            <x-ui.card title="Ma participation" :kicker="$edition?->name">
                <x-slot name="actions">
                    <x-ui.badge :tone="$state->tone()" dot>{{ $state->label() }}</x-ui.badge>
                </x-slot>

                @if ($edition)
                    @php $maximum = $edition->max_slots_per_volunteer; @endphp

                    <div class="rounded-xl bg-zinc-50 p-4 tabular-grid">
                        <p class="text-sm font-bold text-zinc-900">{{ $bookedCount }} / {{ $maximum }} créneaux choisis</p>

                        <div class="mt-3 flex gap-1.5" role="img" aria-label="{{ $bookedCount }} créneau{{ $bookedCount > 1 ? 'x' : '' }} sur {{ $maximum }}">
                            @for ($segment = 1; $segment <= $maximum; $segment++)
                                <span @class([
                                    'h-2 flex-1 rounded-full',
                                    'bg-primary' => $segment <= $bookedCount,
                                    'bg-zinc-200' => $segment > $bookedCount,
                                ])></span>
                            @endfor
                        </div>
                    </div>
                @endif

                <p class="mt-4 text-sm text-zinc-500">{{ $state->description() }}</p>

                <x-slot name="footer">
                    <a href="{{ route('planning.summary') }}" class="inline-flex min-h-touch items-center gap-1.5 text-sm font-bold text-primary hover:text-primary-hover">
                        Voir ma fiche bénévole
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </x-slot>
            </x-ui.card>

            <x-ui.alert title="Mot de passe">
                Pour en changer, déconnectez-vous puis utilisez « Mot de passe oublié ? »
                sur la page de connexion : un lien vous sera envoyé par e-mail.
            </x-ui.alert>
        </div>
    </div>
</x-app-layout>
