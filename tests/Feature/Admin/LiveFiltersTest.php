<?php

use Illuminate\Support\Facades\File;

/**
 * Le contrat du filtrage sans clic, verifie sans navigateur.
 *
 * Ce contrat n'est visible ni du serveur ni du compilateur : le formulaire
 * nomme une zone de resultats, et `live-filters.js` la remplace. Une faute de
 * frappe dans l'identifiant, ou un `x-data` deplace, ne casse rien a la
 * compilation — seulement la recherche, dans le navigateur.
 */
beforeEach(function () {
    $this->edition = salon();
    $this->admin = administrateur($this->edition);

    creneau($this->edition, position: 1);
});

/** @return array<int, string> */
function ecransFiltres(): array
{
    return [
        route('admin.volunteers.index'),
        route('admin.planning'),
    ];
}

it('pose la zone de resultats que chaque formulaire pretend rafraichir', function () {
    foreach (ecransFiltres() as $url) {
        $html = test()->actingAs(test()->admin)->get($url)->assertOk()->getContent();

        expect($html)->toMatch('/x-data="liveFilters\(/');

        preg_match_all("/liveFilters\('([^']+)'\)/", $html, $matches);

        // `toContain` est variadique : un message passe en second argument
        // deviendrait une seconde chaine a chercher. Les echecs se lisent donc
        // sur l'URL en cours d'iteration.
        expect($matches[1])->not->toBeEmpty();

        // Plusieurs zones peuvent etre rejouees, separees par des espaces.
        foreach (preg_split('/\s+/', implode(' ', $matches[1])) as $target) {
            expect($html)->toContain('id="'.$target.'"');
        }
    }
});

it('accroche le composant au formulaire, jamais a un champ', function () {
    // `live-filters.js` lit le formulaire par `$root`. Si `x-data` quittait le
    // `<form>`, `$root` designerait autre chose et `new FormData` leverait.
    foreach (ecransFiltres() as $url) {
        $html = test()->actingAs(test()->admin)->get($url)->assertOk()->getContent();

        preg_match_all('/<(\w+)[^>]*x-data="liveFilters\(/', $html, $matches);

        expect($matches[1])->not->toBeEmpty()
            ->and(array_unique($matches[1]))->toBe(['form']);
    }
});

it('ne lit jamais le formulaire par $el', function () {
    // Les ecouteurs sont poses sur les champs : Alpine resout alors `$el` vers
    // la liste deroulante qui declenche l'evenement, pas vers le formulaire.
    // C'est le piege qui a casse la recherche une premiere fois.
    $script = File::get(resource_path('js/live-filters.js'));

    // On vise l'usage, pas le mot : le commentaire d'en-tete nomme `$el` pour
    // expliquer le piege.
    expect($script)->not->toContain('this.$el')
        ->and($script)->toContain('this.$root');
});

it('garde un bouton de repli pour les navigateurs sans JavaScript', function () {
    // Le bouton n'est retire qu'au demarrage d'Alpine : sans JavaScript, la
    // recherche reste un formulaire GET ordinaire.
    $html = test()->actingAs(test()->admin)
        ->get(route('admin.volunteers.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('x-ref="submit"')
        ->toContain('type="submit"');
});
