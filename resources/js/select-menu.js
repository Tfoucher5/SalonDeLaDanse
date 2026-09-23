/**
 * Liste deroulante habillee a la charte.
 *
 * La liste native d'un <select> ne se stylise pas : ce composant affiche a la
 * place un bouton et un panneau d'options, mais garde le <select> d'origine
 * dans la page, visuellement masque. C'est lui que le formulaire envoie, lui
 * que le clavier et les lecteurs d'ecran utilisent, et lui qui declenche
 * `change` — les filtres sans clic (`live-filters.js`) n'y voient aucune
 * difference. Sans JavaScript, le <select> natif reste simplement visible.
 */
export default () => ({
    open: false,

    ready: false,

    focused: false,

    value: '',

    label: '',

    /** @type {Array<{label: string|null, options: Array<{value: string, label: string, disabled: boolean}>}>} */
    groups: [],

    init() {
        this.read();
        this.ready = true;

        // Une valeur changee au clavier, sur le <select> natif, doit se
        // refleter dans le bouton.
        this.$refs.native.addEventListener('change', () => this.read());
    },

    /**
     * Relit les options du <select> : groupes et options isolees, dans l'ordre.
     */
    read() {
        const select = this.$refs.native;
        const option = (element) => ({
            value: element.value,
            label: element.textContent.trim(),
            disabled: element.disabled,
        });

        this.groups = [];

        for (const child of select.children) {
            if (child.tagName === 'OPTGROUP') {
                this.groups.push({ label: child.label, options: [...child.children].map(option) });
            } else if (this.groups.length === 0 || this.groups.at(-1).label !== null) {
                this.groups.push({ label: null, options: [option(child)] });
            } else {
                this.groups.at(-1).options.push(option(child));
            }
        }

        this.value = select.value;
        this.label = select.selectedOptions[0]?.textContent.trim() ?? '';
    },

    toggle() {
        if (! this.$refs.native.disabled) {
            this.open = ! this.open;
        }
    },

    /**
     * Choisit une option : le <select> natif prend la valeur et annonce le
     * changement, comme si l'utilisateur l'avait fait lui-meme.
     */
    choose(value) {
        const select = this.$refs.native;

        this.open = false;

        if (select.value === value) {
            return;
        }

        select.value = value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    },
});
