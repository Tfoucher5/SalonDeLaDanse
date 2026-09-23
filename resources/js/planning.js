/**
 * Grille de planning sans rechargement.
 *
 * Le serveur reste la seule source de vérité : chaque action passe par les
 * mêmes routes que sans JavaScript, et la page renvoyée est simplement relue
 * pour n'en remplacer que les morceaux qui changent. Les cartes ne bougent pas,
 * les tranches ouvertes le restent et la position de défilement est conservée.
 *
 * Sans JavaScript, ou si la requête échoue, les formulaires et les liens
 * retombent sur leur comportement normal.
 */
export default () => ({
    loading: false,

    init() {
        window.addEventListener('popstate', () => this.showDay(window.location.href, { push: false }));
    },

    /**
     * Récupère une page du planning et la renvoie sous forme de document.
     */
    async fetchDocument(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            ...options,
        });

        if (! response.ok || ! new URL(response.url).pathname.startsWith('/planning')) {
            throw new Error(`Réponse inattendue : ${response.status}`);
        }

        return new DOMParser().parseFromString(await response.text(), 'text/html');
    },

    /**
     * Remplace l'élément `selector` de la page par celui du document reçu.
     */
    replace(nextDocument, selector) {
        const next = nextDocument.querySelector(selector);
        const current = document.querySelector(selector);

        if (next && current) {
            current.replaceWith(next);
        }

        return next;
    },

    /**
     * Le compteur « Mes créneaux » : l'anneau part de sa valeur actuelle au
     * lieu de rejouer son remplissage depuis zéro.
     */
    replaceSummary(nextDocument) {
        const from = document.querySelector('#planning-summary .progress-ring')?.getAttribute('stroke-dasharray');
        const ring = this.replace(nextDocument, '#planning-summary')?.querySelector('.progress-ring');

        if (! ring || ! from) {
            return;
        }

        const to = ring.getAttribute('stroke-dasharray');

        ring.dataset.static = '';
        ring.style.strokeDasharray = from;
        ring.getBoundingClientRect();
        requestAnimationFrame(() => {
            ring.style.strokeDasharray = to;
        });
    },

    /**
     * Les notifications de la réponse remplacent celles de la page.
     */
    replaceToasts(nextDocument) {
        const next = nextDocument.getElementById('toasts');
        const current = document.getElementById('toasts');

        if (next && current) {
            current.innerHTML = next.innerHTML;
        }
    },

    /**
     * Réserver ou retirer un créneau : seules les cartes, les résumés de
     * tranche et le compteur sont rafraîchis, sans toucher à leur ordre.
     */
    async submit(event) {
        const form = event.target.closest('form[data-planning-action]');

        if (! form || event.defaultPrevented) {
            return;
        }

        event.preventDefault();

        try {
            const nextDocument = await this.fetchDocument(form.action, {
                method: 'POST',
                body: new FormData(form),
            });

            this.replaceSummary(nextDocument);

            const actedCardId = form.closest('article')?.id;

            nextDocument.querySelectorAll('article[id^="creneau-"]').forEach((card) => {
                // Le retour sur la carte ne sert qu'après un vrai rechargement.
                card.removeAttribute('x-init');

                if (card.id === actedCardId) {
                    card.dataset.updated = '';
                }

                document.getElementById(card.id)?.replaceWith(card);
            });

            nextDocument.querySelectorAll('[id^="slot-summary-"]').forEach((summary) => {
                const current = document.getElementById(summary.id);

                if (current) {
                    current.innerHTML = summary.innerHTML;
                }
            });

            this.replaceToasts(nextDocument);
        } catch {
            form.submit();
        }
    },

    /**
     * Changer de jour : un simple changement d'onglet, animé dans le sens du
     * déplacement, et l'adresse suit pour que « précédent » fonctionne.
     */
    async openDay(event) {
        const link = event.target.closest('a[data-day-link]');

        if (! link || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
            return;
        }

        event.preventDefault();

        if (link.getAttribute('aria-current') !== 'page') {
            await this.showDay(link.href);
        }
    },

    async showDay(url, { push = true } = {}) {
        if (this.loading) {
            return;
        }

        this.loading = true;

        const currentIndex = Number(document.querySelector('#planning-days [aria-current="page"]')?.dataset.dayIndex ?? 0);

        try {
            const nextDocument = await this.fetchDocument(url);
            const nextIndex = Number(nextDocument.querySelector('#planning-days [aria-current="page"]')?.dataset.dayIndex ?? 0);

            this.replace(nextDocument, '#planning-days');

            const day = this.replace(nextDocument, '#planning-day');

            if (day) {
                day.dataset.enter = nextIndex >= currentIndex ? 'forward' : 'backward';
            }

            if (push) {
                window.history.pushState({}, '', url);
            }

            // Si l'on avait défilé loin, on remonte juste sous les onglets.
            const tabs = document.getElementById('planning-days');

            if (tabs && day && day.getBoundingClientRect().top < 0) {
                window.scrollTo({ top: window.scrollY + day.getBoundingClientRect().top - tabs.offsetHeight - 72, behavior: 'smooth' });
            }
        } catch {
            window.location.href = url;
        } finally {
            this.loading = false;
        }
    },
});
