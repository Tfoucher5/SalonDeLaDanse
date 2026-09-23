<x-guest-layout title="Mentions légales" width="sm:max-w-3xl"
                :back="auth()->check() ? route('dashboard') : url('/')"
                :back-label="auth()->check() ? 'Tableau de bord' : 'Accueil'">
    <x-ui.form-heading
        title="Mentions légales"
        subtitle="Qui édite cet espace bénévoles, où il est hébergé et ce que deviennent vos données." />

    <div class="space-y-8 text-sm leading-relaxed text-zinc-600 sm:text-[0.9375rem]">
        <section aria-labelledby="editeur" class="space-y-3">
            <h2 id="editeur" class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">Éditeur du site</h2>

            <p>
                Cette plateforme de coordination des bénévoles du Salon de la Danse d'Angers est éditée par
                l'association <strong class="font-semibold text-zinc-900">{{ $publisher['name'] }}</strong>,
                organisatrice du Salon.
            </p>

            <x-ui.data-list class="rounded-xl bg-zinc-50 p-4">
                <x-ui.data-row label="Forme juridique">{{ $publisher['status'] }}</x-ui.data-row>
                <x-ui.data-row label="Siège social">{{ $publisher['address'] }}</x-ui.data-row>
                <x-ui.data-row label="N° RNA">{{ $publisher['rna'] }}</x-ui.data-row>
                <x-ui.data-row label="SIREN">{{ $publisher['siren'] }}</x-ui.data-row>
                <x-ui.data-row label="SIRET du siège">{{ $publisher['siret'] }}</x-ui.data-row>
                <x-ui.data-row label="E-mail">
                    <a class="break-all text-primary hover:text-primary-hover" href="mailto:{{ $publisher['email'] }}">{{ $publisher['email'] }}</a>
                </x-ui.data-row>
                <x-ui.data-row label="Téléphone">
                    <a class="text-primary hover:text-primary-hover" href="tel:{{ preg_replace('/\s+/', '', $publisher['phone']) }}">{{ $publisher['phone'] }}</a>
                </x-ui.data-row>
            </x-ui.data-list>

            <p>
                <strong class="font-semibold text-zinc-900">Responsable de la publication :</strong>
                {{ $publisher['director'] }}.
            </p>
        </section>

        <section aria-labelledby="hebergeur" class="space-y-3">
            <h2 id="hebergeur" class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">Hébergement</h2>

            @if ($host !== [])
                <x-ui.data-list class="rounded-xl bg-zinc-50 p-4">
                    @isset($host['name'])
                        <x-ui.data-row label="Hébergeur">{{ $host['name'] }}</x-ui.data-row>
                    @endisset

                    @isset($host['address'])
                        <x-ui.data-row label="Adresse">{{ $host['address'] }}</x-ui.data-row>
                    @endisset

                    @isset($host['website'])
                        <x-ui.data-row label="Site">
                            <a class="break-all text-primary hover:text-primary-hover" href="{{ $host['website'] }}" rel="noopener">{{ $host['website'] }}</a>
                        </x-ui.data-row>
                    @endisset
                </x-ui.data-list>
            @else
                <x-ui.alert>Les coordonnées de l'hébergeur seront publiées ici à la mise en ligne de la plateforme.</x-ui.alert>
            @endif
        </section>

        <section aria-labelledby="objet" class="space-y-3">
            <h2 id="objet" class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">Objet de la plateforme</h2>

            <p>
                La plateforme est réservée aux bénévoles retenus par l'équipe organisatrice, qui y accèdent
                avec un code d'invitation personnel. Elle leur permet de créer leur compte, de choisir leurs
                créneaux de mission pendant le Salon et de consulter leur planning. Elle n'a aucune vocation
                commerciale : aucun paiement n'y est demandé.
            </p>
        </section>

        <section aria-labelledby="donnees" class="space-y-3">
            <h2 id="donnees" class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">Données personnelles</h2>

            <p>
                L'association {{ $publisher['name'] }} est responsable du traitement des données collectées
                sur la plateforme, conformément au Règlement général sur la protection des données (RGPD)
                et à la loi n° 78-17 du 6 janvier 1978 dite « Informatique et Libertés ».
            </p>

            <ul class="space-y-2.5">
                @foreach ([
                    'Données collectées' => 'nom, prénom, adresse e-mail, téléphone, date de naissance, photo d\'identité, mot de passe (stocké chiffré, jamais lisible) et créneaux choisis.',
                    'Finalité' => 'organiser l\'accueil des bénévoles pendant le Salon : composer les plannings, vérifier les jauges de chaque mission, identifier les bénévoles sur place et les joindre en cas de changement.',
                    'Base légale' => 'l\'engagement bénévole que vous prenez auprès de l\'association, auquel ces données sont nécessaires.',
                    'Destinataires' => 'les seuls membres de l\'équipe organisatrice. Les autres bénévoles ne voient jamais votre nom ni votre photo : ils ne voient que le nombre de places restantes sur un créneau. Aucune donnée n\'est vendue, cédée ou transmise à des fins commerciales.',
                    'Durée de conservation' => 'le temps de l\'édition du Salon, puis '.$retentionMonths.' mois au plus après sa clôture, avant suppression.',
                ] as $term => $description)
                    <li class="rounded-xl bg-zinc-50 p-3.5">
                        <strong class="font-semibold text-zinc-900">{{ $term }} :</strong>
                        {{ $description }}
                    </li>
                @endforeach
            </ul>

            <p>
                Vous disposez d'un droit d'accès, de rectification, d'effacement, de limitation et d'opposition
                sur vos données. Votre profil étant verrouillé après inscription, toute correction passe par
                l'équipe organisatrice : écrivez à
                <a class="break-all font-semibold text-primary hover:text-primary-hover" href="mailto:{{ $publisher['email'] }}">{{ $publisher['email'] }}</a>.
                Si vous estimez que vos droits ne sont pas respectés, vous pouvez adresser une réclamation à la
                <a class="font-semibold text-primary hover:text-primary-hover" href="https://www.cnil.fr/fr/plaintes" rel="noopener">CNIL</a>.
            </p>
        </section>

        <section aria-labelledby="cookies" class="space-y-3">
            <h2 id="cookies" class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">Cookies</h2>

            <p>
                La plateforme ne dépose que les cookies strictement nécessaires à son fonctionnement : le cookie
                de session qui vous garde connecté et le jeton de sécurité qui protège les formulaires. Ils ne
                requièrent pas votre consentement. Aucun cookie publicitaire ni outil de mesure d'audience
                n'est utilisé.
            </p>

            <p>
                La police de caractères est chargée depuis le service Google Fonts : votre navigateur contacte
                alors les serveurs de Google, qui reçoivent votre adresse IP.
            </p>
        </section>

        <section aria-labelledby="propriete" class="space-y-3">
            <h2 id="propriete" class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-primary">Propriété intellectuelle</h2>

            <p>
                Le nom et le logotype du Salon de la Danse, les textes et les visuels de la plateforme
                appartiennent à l'association {{ $publisher['name'] }} ou à leurs auteurs respectifs. Toute
                reproduction sans autorisation préalable est interdite.
            </p>
        </section>
    </div>
</x-guest-layout>
