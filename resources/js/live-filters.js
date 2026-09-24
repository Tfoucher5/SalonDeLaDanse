/**
 * Filtres du back-office qui s'appliquent sans clic.
 *
 * Le formulaire reste un formulaire GET ordinaire : sans JavaScript, le bouton
 * « Rechercher » fonctionne comme avant. Une fois Alpine en route, ce composant
 * retire le bouton et rejoue la meme URL en arriere-plan, puis remplace la
 * seule zone de resultats.
 *
 * On ne recharge pas la page : le curseur resterait au debut du champ de
 * recherche a chaque frappe, ce qui rend la saisie impraticable.
 *
 * Le formulaire est retenu une fois pour toutes a l'initialisation. Ni `$el`
 * ni `$root` ne conviennent dans `refresh()` : Alpine les resout depuis le
 * champ qui declenche l'evenement, et chaque liste deroulante (`selectMenu`)
 * ou bloc depliable porte son propre `x-data`. On obtiendrait un <div>, et
 * `new FormData(<div>)` leve. Il est garde hors des donnees du composant :
 * Alpine envelopperait sinon l'element dans un proxy, et `submit()` ou
 * `FormData` le refuseraient.
 *
 * Plusieurs zones peuvent etre rejouees, identifiants separes par des espaces :
 * la premiere porte les resultats et doit exister, les suivantes (onglets de
 * jour, bouton d'export poses dans la barre de filtres) suivent si presentes.
 *
 * @param {string} targetIds identifiant(s) de la ou des zones a remplacer
 */
export default function liveFilters(targetIds) {
    const ids = targetIds.split(/\s+/).filter(Boolean);

    /** @type {HTMLFormElement|null} */
    let form = null;

    return {
        /** La requete en cours, annulable. */
        controller: null,

        busy: false,

        init() {
            form = this.$el;

            // Entree dans le champ de recherche soumettrait le formulaire et
            // rechargerait la page : on filtre en arriere-plan a la place.
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                this.refresh();
            });

            // Le bouton n'a de sens que sans JavaScript. S'il reste, un clic
            // rechargerait la page et ferait perdre le focus.
            this.$refs.submit?.remove();
        },

        async refresh() {
            const target = document.getElementById(ids[0]);

            // Zone introuvable : on retombe sur la soumission classique plutot
            // que de ne rien faire du tout.
            if (target === null) {
                form.submit();

                return;
            }

            // Une frappe rapide lance plusieurs requetes ; sans annulation, la
            // plus lente ecraserait la plus recente.
            this.controller?.abort();
            this.controller = new AbortController();
            this.busy = true;

            const url = this.url();

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: this.controller.signal,
                });

                if (! response.ok) {
                    throw new Error(`Réponse ${response.status}`);
                }

                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                if (page.getElementById(ids[0]) === null) {
                    throw new Error('Zone de résultats absente de la réponse');
                }

                ids.forEach((id) => {
                    const current = document.getElementById(id);
                    const fresh = page.getElementById(id);

                    if (current !== null && fresh !== null) {
                        current.innerHTML = fresh.innerHTML;
                    }
                });

                // L'URL suit les criteres : une recherche reste partageable et
                // survit a un rechargement.
                window.history.replaceState({}, '', url);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    form.submit();
                }
            } finally {
                this.busy = false;
            }
        },

        /**
         * Un lien qui ne change qu'un critere, comme un onglet de jour : sa
         * valeur passe dans le champ du formulaire, puis on rejoue en
         * arriere-plan au lieu de recharger la page. Le lien garde son `href`
         * pour les navigateurs sans JavaScript et l'ouverture dans un onglet.
         *
         * La zone de resultats glisse du cote vers lequel on s'est deplace,
         * comme sur la grille du benevole (`.planning-day`, app.css).
         *
         * @param {MouseEvent} event clic delegue depuis le conteneur des liens
         * @param {string} field nom du champ et du parametre d'URL
         */
        async follow(event, field) {
            const link = event.target.closest('a[href]');
            const input = form.elements.namedItem(field);

            if (link === null || input === null || event.button !== 0
                || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const value = new URL(link.href).searchParams.get(field);

            if (value === null) {
                return;
            }

            event.preventDefault();

            if (value === input.value) {
                return;
            }

            // `currentTarget` ne vaut que pendant la diffusion de l'evenement :
            // on le retient avant d'attendre la reponse.
            const container = event.currentTarget;
            const links = [...container.querySelectorAll('a[href]')];
            const current = links.findIndex((candidate) => candidate.getAttribute('aria-current') === 'page');
            const direction = links.indexOf(link) >= current ? 'forward' : 'backward';

            input.value = value;
            await this.refresh();

            const target = document.getElementById(ids[0]);

            if (target === null) {
                return;
            }

            // Relancer l'animation : l'attribut doit disparaitre un instant,
            // sinon le navigateur ne voit aucun changement.
            delete target.dataset.enter;
            void target.offsetWidth;
            target.dataset.enter = direction;

            // Si l'on avait defile loin, on remonte juste sous les liens.
            const top = target.getBoundingClientRect().top;
            const bottom = container.getBoundingClientRect().bottom;

            if (top < bottom) {
                window.scrollTo({ top: window.scrollY + top - bottom - 12, behavior: 'smooth' });
            }
        },

        /**
         * L'URL du formulaire, criteres vides ecartes : sans ce tri, la barre
         * d'adresse se remplirait de `?name=&mission=&status=`.
         */
        url() {
            const params = new URLSearchParams();

            new FormData(form).forEach((value, key) => {
                if (value !== '') {
                    params.append(key, value);
                }
            });

            const query = params.toString();

            return query === '' ? form.action : `${form.action}?${query}`;
        },
    };
}
