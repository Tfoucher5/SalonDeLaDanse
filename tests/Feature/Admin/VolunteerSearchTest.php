<?php

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * Recherche multi-critères : nom, prénom, mission, statut de validation, jour.
 * Chaque critère est vérifié seul, puis les critères sont combinés.
 */
beforeEach(function () {
    $this->edition = salon();
    $this->admin = administrateur($this->edition);

    $this->accueil = creneau($this->edition, position: 1, date: '2027-05-14', capacity: 10);
    $this->accueil->mission->update(['name' => 'Accueil exposants']);

    $this->loges = creneau($this->edition, position: 3, date: '2027-05-15', capacity: 10);
    $this->loges->mission->update(['name' => 'Loges danseurs']);

    // Camille : Accueil le vendredi, planning valide.
    $this->camille = User::factory()->forEdition($this->edition)->validatedPlanning()->create([
        'first_name' => 'Camille', 'last_name' => 'Dorel',
    ]);
    Assignment::factory()->create(['user_id' => $this->camille->id, 'shift_id' => $this->accueil->id]);

    // Naim : Loges le samedi, planning en attente.
    $this->naim = benevole($this->edition);
    $this->naim->update(['first_name' => 'Naim', 'last_name' => 'Belkacem']);
    Assignment::factory()->create(['user_id' => $this->naim->id, 'shift_id' => $this->loges->id]);
});

function chercher(array $criteria = []): TestResponse
{
    return test()->actingAs(test()->admin)->get(route('admin.volunteers.index', $criteria));
}

it('liste tous les benevoles sans critere', function () {
    chercher()->assertOk()->assertSee('Camille Dorel')->assertSee('Naim Belkacem');
});

it('ne liste pas les administrateurs parmi les benevoles', function () {
    // Un second administrateur : le nom de celui qui consulte figure de toute
    // facon dans la barre de navigation, il ne prouverait rien.
    administrateur($this->edition)->update(['first_name' => 'Gwen', 'last_name' => 'Ollivier']);

    chercher()->assertOk()->assertDontSee('Gwen Ollivier');
});

it('cherche sur le nom comme sur le prenom', function () {
    chercher(['name' => 'Belkacem'])->assertSee('Naim Belkacem')->assertDontSee('Camille Dorel');
    chercher(['name' => 'Camille'])->assertSee('Camille Dorel')->assertDontSee('Naim Belkacem');
    chercher(['name' => 'kace'])->assertSee('Naim Belkacem')->assertDontSee('Camille Dorel');
});

it('cherche par mission', function () {
    chercher(['mission' => $this->loges->mission_id])
        ->assertSee('Naim Belkacem')
        ->assertDontSee('Camille Dorel');
});

it('cherche par statut de validation', function () {
    chercher(['status' => 'validated'])->assertSee('Camille Dorel')->assertDontSee('Naim Belkacem');
    chercher(['status' => 'pending'])->assertSee('Naim Belkacem')->assertDontSee('Camille Dorel');
});

it('cherche par jour', function () {
    chercher(['day' => '2027-05-15'])->assertSee('Naim Belkacem')->assertDontSee('Camille Dorel');
    chercher(['day' => '2027-05-14'])->assertSee('Camille Dorel')->assertDontSee('Naim Belkacem');
});

it('combine les criteres', function () {
    chercher(['mission' => $this->accueil->mission_id, 'status' => 'validated'])
        ->assertSee('Camille Dorel');

    chercher(['mission' => $this->accueil->mission_id, 'status' => 'pending'])
        ->assertDontSee('Camille Dorel')
        ->assertSee('Aucun bénévole ne correspond à cette recherche');
});

it('ignore un critere vide plutot que de vider la liste', function () {
    chercher(['name' => '', 'mission' => '', 'status' => '', 'day' => ''])
        ->assertOk()
        ->assertSee('Camille Dorel')
        ->assertSee('Naim Belkacem');
});

it('refuse une mission inconnue et un statut inexistant', function () {
    chercher(['mission' => 99999])->assertSessionHasErrors('mission');
    chercher(['status' => 'peut-etre'])->assertSessionHasErrors('status');
    chercher(['day' => '14/05/2027'])->assertSessionHasErrors('day');
});

it('ecarte un jour hors edition sans vider la liste', function () {
    chercher(['day' => '2030-01-01'])
        ->assertOk()
        ->assertSee('Camille Dorel')
        ->assertSee('Naim Belkacem');
});

it('ne cherche pas au-dela de l edition courante', function () {
    $autre = salon();
    $etranger = benevole($autre);
    $etranger->update(['first_name' => 'Elio', 'last_name' => 'Vasseur']);

    chercher()->assertOk()->assertDontSee('Elio Vasseur');
});

it('affiche le nombre de creneaux de chaque benevole', function () {
    Assignment::factory()->create([
        'user_id' => $this->naim->id,
        'shift_id' => creneau($this->edition, position: 5, date: '2027-05-16')->id,
    ]);

    $content = chercher(['name' => 'Belkacem'])->assertOk()->getContent();

    expect($content)->toContain('<td>2</td>');
});

it('mene a la fiche du benevole', function () {
    chercher(['name' => 'Dorel'])
        ->assertOk()
        ->assertSee(route('admin.volunteers.show', $this->camille), escape: false);
});

it('pagine au-dela de vingt-cinq benevoles', function () {
    User::factory()->count(25)->forEdition($this->edition)->create();

    chercher()->assertOk()->assertSee('sur 27')->assertSee('Suivant');
});
