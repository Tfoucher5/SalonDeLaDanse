<?php

use App\Models\Assignment;

/**
 * La porte du back-office. Un bénévole authentifié n'est pas un visiteur
 * égaré : il reçoit un 403, pas une redirection vers la connexion.
 */

/** @return array<int, string> */
function routesDuBackOffice(): array
{
    return [
        route('admin.dashboard'),
        route('admin.planning'),
        route('admin.volunteers.index'),
    ];
}

it('redirige un visiteur anonyme vers la connexion', function () {
    foreach (routesDuBackOffice() as $url) {
        $this->get($url)->assertRedirect('/login');
    }
});

it('refuse le back-office a un benevole', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    foreach (routesDuBackOffice() as $url) {
        $this->actingAs($volunteer)->get($url)->assertForbidden();
    }

    $this->actingAs($volunteer)
        ->get(route('admin.volunteers.show', $volunteer))
        ->assertForbidden();
});

it('ouvre le back-office a un administrateur', function () {
    $edition = salon();
    $admin = administrateur($edition);

    foreach (routesDuBackOffice() as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }

    $this->actingAs($admin)
        ->get(route('admin.volunteers.show', benevole($edition)))
        ->assertOk();
});

it('renvoie l administrateur vers le back-office depuis tout ecran benevole', function () {
    $edition = salon();
    $admin = administrateur($edition);

    foreach ([route('dashboard'), route('planning.index'), route('planning.summary')] as $url) {
        $this->actingAs($admin)->get($url)->assertRedirect(route('admin.dashboard'));
    }

    // Y compris sur l ecriture du planning : l administrateur ne compose pas
    // le sien, il attribuera les creneaux depuis le back-office.
    $this->actingAs($admin)
        ->post(route('planning.shifts.store', creneau($edition, position: 1)))
        ->assertRedirect(route('admin.dashboard'));
});

it('laisse le benevole chez lui', function () {
    $edition = salon();

    $this->actingAs(benevole($edition))->get(route('dashboard'))->assertOk();
    $this->actingAs(benevole($edition))->get(route('planning.index'))->assertOk();
});

it('ne mele jamais les deux navigations', function () {
    $edition = salon();

    // Cote administrateur : aucun lien vers le planning ni vers la fiche.
    $this->actingAs(administrateur($edition))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Administration')
        ->assertDontSee(route('planning.index'), escape: false)
        ->assertDontSee(route('planning.summary'), escape: false);

    // Cote benevole : aucune trace du back-office.
    $this->actingAs(benevole($edition))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.dashboard'), escape: false)
        ->assertDontSee('Administration');
});

it('coiffe les pages partagees de l en-tete du compte connecte', function () {
    $edition = salon();

    // Le profil est le seul ecran commun aux deux roles : chacun doit y
    // retrouver sa propre navigation, et rien de ce qui appartient a l autre.
    //
    // Les URL sont comparees guillemet ferme compris : `/planning` est un
    // prefixe de `/planning/fiche`, et une simple recherche de sous-chaine
    // rendrait ce test faussement rouge.
    $this->actingAs(administrateur($edition))
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Administration')
        ->assertSee(route('admin.volunteers.index').'"', escape: false)
        ->assertDontSee(route('planning.index').'"', escape: false)
        ->assertDontSee(route('planning.summary').'"', escape: false)
        ->assertDontSee('Ma participation');

    $this->actingAs(benevole($edition))
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee(route('planning.index').'"', escape: false)
        ->assertSee('Ma participation')
        ->assertDontSee('Administration');
});

it('reste consultable sans aucune edition en base', function () {
    $this->actingAs(administrateur())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Aucune édition active');

    $this->actingAs(administrateur())
        ->get(route('admin.volunteers.index'))
        ->assertOk()
        ->assertSee('Aucune édition active');

    $this->actingAs(administrateur())
        ->get(route('admin.planning'))
        ->assertOk()
        ->assertSee('Aucune édition active');

    // Sans édition, il n'y a rien à exporter : mieux vaut un 404 franc qu'un
    // classeur vide qu'on croirait complet.
    $this->actingAs(administrateur())
        ->get(route('admin.exports.download', ['dataset' => 'planning', 'format' => 'csv']))
        ->assertNotFound();
});

it('depose chaque role chez lui a la connexion', function () {
    $edition = salon();

    $this->post(route('login'), [
        'email' => benevole($edition)->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->post(route('logout'));

    $this->post(route('login'), [
        'email' => administrateur($edition)->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard', absolute: false));
});

it('respecte la page demandee avant la connexion', function () {
    $edition = salon();
    $admin = administrateur($edition);

    // L administrateur visait la liste des benevoles : la connexion l y ramene,
    // elle ne le renvoie pas a la vue d ensemble.
    $this->get(route('admin.volunteers.index'))->assertRedirect('/login');

    $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.volunteers.index'));
});

it('renvoie chez lui un compte deja connecte qui rouvre la connexion', function () {
    $edition = salon();

    $this->actingAs(benevole($edition))->get('/login')->assertRedirect(route('dashboard'));
    $this->actingAs(administrateur($edition))->get('/login')->assertRedirect(route('admin.dashboard'));
});

it('affiche la fiche d un benevole avec son planning complet', function () {
    $edition = salon();
    $volunteer = benevole($edition);
    $volunteer->update(['first_name' => 'Solene', 'last_name' => 'Marchand']);

    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $volunteer->id,
        'shift_id' => creneau($edition, position: 2, date: '2027-05-15', restricted: true)->id,
    ]);

    $this->actingAs(administrateur($edition))
        ->get(route('admin.volunteers.show', $volunteer))
        ->assertOk()
        ->assertSee('Solene Marchand')
        ->assertSee($volunteer->email)
        ->assertSee('10:00 - 12:00')
        ->assertSee('Restreinte')
        ->assertSee('Équipe organisatrice');
});
