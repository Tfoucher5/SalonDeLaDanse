{{--
    Planche de reference de la charte. Hors production uniquement.

    Regle de composition : une page nouvelle se monte avec `x-ui.*`. Si un
    besoin n'a pas de composant ici, on ajoute le composant — on ne recopie pas
    des classes dans une vue.
--}}

<x-app-layout title="Charte graphique" width="xl">
    <x-slot name="header">
        <x-ui.page-header
            title="Charte graphique"
            eyebrow="Référence interne"
            subtitle="Tout écran de la plateforme se compose avec ces briques. Aucune couleur en dur dans les vues." />
    </x-slot>

    <x-ui.card title="L'intention" subtitle="Le principe qui tranche tous les arbitrages.">
        <p class="text-zinc-900">
            Une interface chaleureuse et rythmée, inspirée de la scène : fond porcelaine,
            action en terracotta, états en prune, disponibilité en émeraude. La couleur
            d'information (les jauges) reste distincte de la couleur d'action.
        </p>

        <ul class="mt-4 space-y-2 text-sm text-zinc-500">
            <li>Terracotta pour ce sur quoi on peut cliquer, prune pour les états.</li>
            <li>« Complet » est gris, jamais rouge. Le rouge dit l'échec, pas l'état normal.</li>
            <li>Ombres chaudes nommées (card, lift, cta, overlay), jamais les ombres grises génériques.</li>
            <li>Capsules pour badges et pastilles, 12 px sur les commandes, 16 px sur les cartes.</li>
            <li>La couleur ne suffit jamais : tout état porte aussi un mot.</li>
        </ul>
    </x-ui.card>

    @foreach ([['Neutres — la structure', $neutrals], ["Accent — l'action", $accents], ["Fonctionnelles — l'information", $functionals]] as [$paletteTitle, $swatches])
        <x-ui.card :title="$paletteTitle">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($swatches as [$token, $hex, $usage, $swatchClass])
                    <div class="flex items-center gap-3 rounded-xl bg-zinc-50 p-3">
                        <span class="h-10 w-10 shrink-0 rounded-lg ring-1 ring-zinc-900/10 {{ $swatchClass }}"></span>

                        <div class="min-w-0">
                            <p class="tabular-grid text-sm font-medium text-zinc-900">{{ $token }} · {{ $hex }}</p>
                            <p class="text-sm text-zinc-500">{{ $usage }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endforeach

    <x-ui.card title="Typographie" subtitle="Plus Jakarta Sans, une seule famille. La hiérarchie se fait à la taille et à la graisse.">
        <div class="space-y-4">
            <div>
                <p class="text-2xl font-semibold text-zinc-900 sm:text-3xl">Titre de page</p>
                <p class="text-sm text-zinc-500">24-30 px · 600 · zinc-900 · x-ui.page-header</p>
            </div>

            <div>
                <p class="text-lg font-semibold text-zinc-900">Titre de section</p>
                <p class="text-sm text-zinc-500">18-20 px · 600 · zinc-900 · titre de x-ui.card</p>
            </div>

            <div>
                <p class="text-zinc-900">Texte courant, celui qu'on lit vraiment.</p>
                <p class="text-sm text-zinc-500">15-16 px · 400 · zinc-900</p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Texte secondaire et libellés.</p>
                <p class="text-sm text-zinc-500">13-14 px · 400-500 · zinc-500</p>
            </div>

            <div class="tabular-grid rounded-xl bg-zinc-50 p-3">
                <p class="text-zinc-900">08:30 - 10:00 · 11 places restantes</p>
                <p class="text-sm text-zinc-500">Chiffres tabulaires : classe tabular-grid sur toute grille ou tableau.</p>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Boutons" subtitle="Un seul bouton primaire par écran. Taille touch (44 px) partout où le doigt clique.">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button variant="primary" type="button">Valider définitivement</x-ui.button>
            <x-ui.button type="button">Secondaire</x-ui.button>
            <x-ui.button variant="ghost" type="button">Discret</x-ui.button>
            <x-ui.button variant="booked" type="button">Se désister</x-ui.button>
            <x-ui.button variant="danger" type="button">Supprimer</x-ui.button>
            <x-ui.button variant="primary" type="button" disabled>Désactivé</x-ui.button>
        </div>

        <div class="mt-4 max-w-sm">
            <x-ui.button variant="primary" size="touch" type="button" block>Pleine largeur, cible tactile</x-ui.button>
        </div>
    </x-ui.card>

    <x-ui.card title="Badges" subtitle="La couleur confirme, le mot informe.">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.badge>Neutre</x-ui.badge>
            <x-ui.badge tone="primary">Non validé</x-ui.badge>
            <x-ui.badge tone="primary-outline">Réservé</x-ui.badge>
            <x-ui.badge tone="free">Validé</x-ui.badge>
            <x-ui.badge tone="tight">Presque complet</x-ui.badge>
            <x-ui.badge tone="full">Complet</x-ui.badge>
            <x-ui.badge tone="danger">Refusé</x-ui.badge>
        </div>
    </x-ui.card>

    <x-ui.card title="Messages" subtitle="Le rouge est réservé à ce qui a échoué ou à ce qui détruit.">
        <div class="space-y-3">
            <x-ui.alert>Le planning est en consultation seule : aucune modification n'est possible.</x-ui.alert>
            <x-ui.alert tone="primary">Créneau ajouté à votre planning.</x-ui.alert>
            <x-ui.alert tone="success" title="Planning validé">Votre fiche récapitulative est disponible.</x-ui.alert>
            <x-ui.alert tone="attention">Il vous manque un créneau pour atteindre le minimum.</x-ui.alert>
            <x-ui.alert tone="danger">Vous avez déjà 3 créneaux : retirez-en un pour en ajouter un autre.</x-ui.alert>
        </div>
    </x-ui.card>

    <x-ui.card title="Champs" subtitle="Une erreur affiche toujours un message : il dit quoi corriger.">
        <div class="grid max-w-2xl gap-4 sm:grid-cols-2">
            <x-ui.field label="Prénom" for="demo-first-name" required>
                <x-text-input id="demo-first-name" type="text" value="Camille" />
            </x-ui.field>

            <x-ui.field label="Téléphone" for="demo-phone" hint="Format libre.">
                <x-text-input id="demo-phone" type="tel" placeholder="06 12 34 56 78" />
            </x-ui.field>

            <x-ui.field label="Code d'invitation" for="demo-code" :messages="['Ce code a déjà été utilisé.']">
                <x-text-input id="demo-code" type="text" value="SALON-4F2A9C" class="tabular-grid uppercase tracking-widest" />
            </x-ui.field>

            <x-ui.field label="Champ désactivé" for="demo-disabled">
                <x-text-input id="demo-disabled" type="text" value="Verrouillé" disabled />
            </x-ui.field>

            <x-ui.field label="Mission" for="demo-select" hint="Filtre du back-office.">
                <x-ui.select id="demo-select">
                    <option>Toutes les missions</option>
                    <option>Accueil exposants</option>
                    <option>Billetterie (restreinte)</option>
                </x-ui.select>
            </x-ui.field>

            <x-ui.field label="Liste désactivée" for="demo-select-disabled">
                <x-ui.select id="demo-select-disabled" disabled>
                    <option>Aucune édition active</option>
                </x-ui.select>
            </x-ui.field>
        </div>
    </x-ui.card>

    <x-ui.card title="Jauges" subtitle="Les places restantes en toutes lettres. L'écran reste lisible en niveaux de gris.">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.gauge level="free" label="3 places restantes" :remaining="3" :capacity="4" />
            <x-ui.gauge level="tight" label="1 place restante" :remaining="1" :capacity="4" />
            <x-ui.gauge level="full" label="Complet" :remaining="0" :capacity="4" />
        </div>
    </x-ui.card>

    <x-ui.card title="Chiffres" subtitle="Compteurs du dashboard et du back-office.">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-ui.stat label="Bénévoles inscrits" value="112" hint="sur 130 attendus" />
            <x-ui.stat label="Plannings validés" value="87" />
            <x-ui.stat label="Taux de remplissage" value="76 %" />
        </div>
    </x-ui.card>

    <x-ui.card title="Tableau" subtitle="Bordures, pas de rayures. Survol de ligne en zinc-100.">
        <x-ui.table>
            <x-slot name="head">
                <th scope="col">Bénévole</th>
                <th scope="col">Mission</th>
                <th scope="col">Créneau</th>
                <th scope="col">Statut</th>
            </x-slot>

            @foreach ([['Camille Doré', 'Accueil', 'Sam. 08:30 - 10:00', 'free', 'Validé'], ['Naïm Belkacem', 'Billetterie', 'Sam. 14:00 - 16:00', 'primary', 'Non validé'], ['Alex Rivière', 'Logistique', 'Dim. 10:00 - 12:00', 'full', 'Verrouillé']] as [$name, $mission, $schedule, $tone, $status])
                <tr class="tabular-grid">
                    <td>{{ $name }}</td>
                    <td>{{ $mission }}</td>
                    <td>{{ $schedule }}</td>
                    <td><x-ui.badge :tone="$tone">{{ $status }}</x-ui.badge></td>
                </tr>
            @endforeach
        </x-ui.table>
    </x-ui.card>

    <x-ui.card title="Pagination" subtitle="Deux commandes et un compteur : la recherche fait le reste.">
        <x-ui.pagination :paginator="$paginator" />
    </x-ui.card>

    <x-ui.card title="Absence de données">
        <x-ui.empty title="Aucun bénévole ne correspond à cette recherche">
            Élargissez les critères, ou vérifiez l'orthographe du nom.
        </x-ui.empty>
    </x-ui.card>

    <x-ui.card title="Carte de créneau" subtitle="Le composant le plus manipulé de la plateforme : x-planning.shift-card.">
        <x-ui.table>
            <x-slot name="head">
                <th scope="col">État</th>
                <th scope="col">Traitement</th>
            </x-slot>

            @foreach ([['Disponible', 'Tuile blanche : « Disponibilité », badge de places, jauge à segments (une place libre par segment), bouton primaire « Réserver ce créneau » pleine largeur.'], ['Presque complet', 'Segments et badge ambre ; « Dernière place ! » sur la toute dernière place.'], ['Réservé par le bénévole', 'Anneau émeraude, badge « Vous participez », bouton « Se désister » (variante booked).'], ['Complet ou bloqué par une règle', 'Fond zinc-50, jauge grise, le motif en clair dans une tuile grise à la place du bouton.']] as [$cardState, $treatment])
                <tr>
                    <td class="font-medium">{{ $cardState }}</td>
                    <td>{{ $treatment }}</td>
                </tr>
            @endforeach
        </x-ui.table>

        <x-ui.alert class="mt-4">
            Une règle métier qui bloque doit toujours se justifier à l'écran.
            « Vous avez déjà 3 créneaux » est une information utile, « indisponible » ne l'est pas.
        </x-ui.alert>
    </x-ui.card>
</x-app-layout>
