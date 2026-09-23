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
 * @param {string} targetId identifiant de la zone a remplacer
 */
export default function liveFilters(targetId) {
    return {
        /** La requete en cours, annulable. */
        controller: null,

        busy: false,

        init() {
            // Le bouton n'a de sens que sans JavaScript. S'il reste, un clic
            // rechargerait la page et ferait perdre le focus.
            this.$refs.submit?.remove();
        },

        async refresh() {
            const target = document.getElementById(targetId);

            // Zone introuvable : on retombe sur la soumission classique plutot
            // que de ne rien faire du tout.
            if (target === null) {
                this.$el.submit();

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
                const fresh = page.getElementById(targetId);

                if (fresh === null) {
                    throw new Error('Zone de résultats absente de la réponse');
                }

                target.innerHTML = fresh.innerHTML;

                // L'URL suit les criteres : une recherche reste partageable et
                // survit a un rechargement.
                window.history.replaceState({}, '', url);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    this.$el.submit();
                }
            } finally {
                this.busy = false;
            }
        },

        /**
         * L'URL du formulaire, criteres vides ecartes : sans ce tri, la barre
         * d'adresse se remplirait de `?name=&mission=&status=`.
         */
        url() {
            const params = new URLSearchParams();

            new FormData(this.$el).forEach((value, key) => {
                if (value !== '') {
                    params.append(key, value);
                }
            });

            const query = params.toString();

            return query === '' ? this.$el.action : `${this.$el.action}?${query}`;
        },
    };
}
