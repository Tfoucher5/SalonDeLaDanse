/**
 * La selection manuelle de la page « Badges ».
 *
 * Les cases a cocher vivent dans la zone de resultats, que `liveFilters`
 * remplace a chaque changement de critere : leur etat serait perdu a chaque
 * frappe. La selection est donc gardee ici, sur un element qui englobe la
 * zone sans en faire partie. Les nouvelles cases qu'Alpine initialise apres
 * un remplacement relisent `isSelected()` : la selection se reapplique seule.
 *
 * Un bénévole coche puis masque par un filtre reste selectionne : le compteur
 * le dit, et des champs caches hors de la zone l'envoient avec le formulaire.
 *
 * Comme `liveFilters`, l'element racine est retenu a l'initialisation : dans
 * une methode appelee depuis une case, `$el` designerait la case.
 */
export default function badgeSelection() {
    /** @type {HTMLElement|null} */
    let root = null;

    return {
        /** @type {number[]} */
        selected: [],

        init() {
            root = this.$el;
        },

        isSelected(id) {
            return this.selected.includes(id);
        },

        toggle(id, checked) {
            this.selected = checked
                ? [...new Set([...this.selected, id])]
                : this.selected.filter((selectedId) => selectedId !== id);
        },

        /** Coche tout ce que les criteres affichent en ce moment. */
        checkAll() {
            this.selected = [...new Set([...this.selected, ...this.visibleIds()])];
        },

        uncheckAll() {
            this.selected = [];
        },

        visibleIds() {
            return [...root.querySelectorAll('[data-badge-volunteer]')].map((box) => Number(box.value));
        },
    };
}
