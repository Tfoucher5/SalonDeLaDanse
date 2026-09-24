/**
 * La fenetre de scan des badges, dans le back-office.
 *
 * Elle vit dans le modal `badge-scanner` et en lit l'etat `show` : la camera
 * s'allume a l'ouverture et s'eteint a la fermeture, pour ne jamais filmer en
 * arriere-plan. Un code lu met la camera en pause et part au serveur en
 * arriere-plan ; le verdict s'affiche dans la fenetre, sans recharger la page.
 *
 * qr-scanner n'est charge qu'a la premiere ouverture : les autres ecrans du
 * back-office ne paient pas son poids.
 *
 * Le lecteur est garde hors des donnees du composant : Alpine l'envelopperait
 * dans un proxy, et ses methodes internes le refuseraient.
 *
 * @param {string} endpoint adresse du controle JSON
 */
export default function badgeScanner(endpoint) {
    /** @type {import('qr-scanner').default|null} */
    let scanner = null;

    return {
        /** idle, starting, scanning, checking, result */
        phase: 'idle',

        /** La camera a-t-elle pu demarrer ? Sinon, la saisie prend le relais. */
        cameraReady: false,

        cameraError: '',

        manualCode: '',

        /** @type {{status: string, title: string, message: string, volunteer: ?object}|null} */
        result: null,

        init() {
            this.$watch('show', (open) => (open ? this.start() : this.stop()));
        },

        async start() {
            this.result = null;
            this.manualCode = '';

            // Les navigateurs n'ouvrent la camera qu'en HTTPS (ou sur
            // localhost) : on le dit plutot que d'attendre un refus muet.
            if (! window.isSecureContext || ! navigator.mediaDevices) {
                this.fail('La caméra demande une connexion sécurisée (HTTPS). Saisissez l\'identifiant imprimé sur le badge.');

                return;
            }

            this.phase = 'starting';

            try {
                const { default: QrScanner } = await import('qr-scanner');

                scanner ??= new QrScanner(this.$refs.video, (scan) => this.check(scan.data), {
                    preferredCamera: 'environment',
                    highlightScanRegion: true,
                    maxScansPerSecond: 8,
                    returnDetailedScanResult: true,
                });

                await scanner.start();
                this.cameraReady = true;
                this.cameraError = '';
                this.phase = 'scanning';
            } catch (error) {
                this.fail('Caméra indisponible ou refusée. Autorisez-la dans le navigateur, ou saisissez l\'identifiant du badge.');
            }
        },

        stop() {
            scanner?.stop();
            this.phase = 'idle';
        },

        fail(message) {
            this.cameraReady = false;
            this.cameraError = message;
            this.phase = 'idle';
        },

        /**
         * Un code lu ou saisi. Le lecteur rappelle plusieurs fois par seconde
         * tant que le badge reste devant l'objectif : seul le premier compte.
         */
        async check(code) {
            if (this.phase === 'checking' || this.phase === 'result') {
                return;
            }

            scanner?.pause();
            this.phase = 'checking';

            try {
                const response = await fetch(`${endpoint}?${new URLSearchParams({ code })}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (! response.ok) {
                    throw new Error(`Réponse ${response.status}`);
                }

                this.result = await response.json();
            } catch (error) {
                this.result = {
                    status: 'error',
                    title: 'Vérification impossible',
                    message: 'Le serveur n\'a pas répondu, ou votre session a expiré. Réessayez.',
                    volunteer: null,
                };
            }

            // Un retour au toucher : on sait sans regarder qu'un badge a ete lu.
            navigator.vibrate?.(this.result.status === 'valid' ? 80 : [80, 60, 80]);

            this.phase = 'result';
        },

        submitManual() {
            const code = this.manualCode.trim();

            if (code !== '') {
                this.check(code);
            }
        },

        /** Le badge suivant : la camera reprend la ou elle s'etait arretee. */
        async next() {
            this.result = null;
            this.manualCode = '';

            if (! this.cameraReady || scanner === null) {
                this.phase = 'idle';

                return;
            }

            this.phase = 'scanning';
            await scanner.start();
        },

        get isValid() {
            return this.result?.status === 'valid';
        },
    };
}
