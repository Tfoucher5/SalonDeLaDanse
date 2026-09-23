<?php

use App\Models\Assignment;
use App\Models\InvitationCode;
use App\Models\Shift;
use App\Models\User;
use App\Services\EditionOverview;

/**
 * Justesse des compteurs. Un chiffre faux est pire que pas de chiffre :
 * chaque compteur est confronté à une base dont on connaît le contenu exact.
 */
it('compte les benevoles attendus, les comptes crees et les plannings valides', function () {
    $edition = salon();

    InvitationCode::factory()->count(5)->create(['edition_id' => $edition->id]);

    // Trois comptes : deux plannings valides, un encore en attente.
    User::factory()->count(2)->forEdition($edition)->validatedPlanning()->create();
    benevole($edition);

    // Un administrateur n est pas un benevole : il ne doit entrer dans aucun compteur.
    administrateur($edition);

    $headcount = app(EditionOverview::class)->headcount($edition);

    expect($headcount)->toMatchArray([
        'expected' => 5,
        'accounts' => 3,
        'validated' => 2,
        'pending' => 1,
        'codes_left' => 5,
    ]);
});

it('retire des codes disponibles ceux qui ont ete consommes', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    InvitationCode::factory()->count(2)->create(['edition_id' => $edition->id]);
    InvitationCode::factory()->used($volunteer)->create(['edition_id' => $edition->id]);

    expect(app(EditionOverview::class)->headcount($edition))
        ->toMatchArray(['expected' => 3, 'codes_left' => 2]);
});

it('calcule le taux de remplissage global sur les places, pas sur les creneaux', function () {
    $edition = salon();

    // Deux creneaux, 10 places offertes, 3 prises : 30 %.
    $large = creneau($edition, position: 1, capacity: 6);
    $small = creneau($edition, position: 2, capacity: 4);

    Assignment::factory()->count(2)->sequence(
        ['user_id' => benevole($edition)->id],
        ['user_id' => benevole($edition)->id],
    )->create(['shift_id' => $large->id]);

    Assignment::factory()->create(['user_id' => benevole($edition)->id, 'shift_id' => $small->id]);

    expect(app(EditionOverview::class)->fillRate($edition))
        ->toMatchArray(['capacity' => 10, 'taken' => 3, 'remaining' => 7, 'rate' => 30]);
});

it('ventile le remplissage par jour', function () {
    $edition = salon();

    $vendredi = creneau($edition, position: 1, date: '2027-05-14', capacity: 4);
    $samedi = creneau($edition, position: 1, date: '2027-05-15', capacity: 4);

    Assignment::factory()->create(['user_id' => benevole($edition)->id, 'shift_id' => $vendredi->id]);
    Assignment::factory()->create(['user_id' => benevole($edition)->id, 'shift_id' => $samedi->id]);
    Assignment::factory()->create(['user_id' => benevole($edition)->id, 'shift_id' => $samedi->id]);

    $byDay = app(EditionOverview::class)->fillRateByDay($edition)->keyBy('key');

    expect($byDay->get('2027-05-14'))->toMatchArray(['taken' => 1, 'capacity' => 4, 'rate' => 25])
        ->and($byDay->get('2027-05-15'))->toMatchArray(['taken' => 2, 'capacity' => 4, 'rate' => 50])
        ->and($byDay->keys()->all())->toBe(['2027-05-14', '2027-05-15']);
});

it('ventile le remplissage par mission, missions restreintes comprises', function () {
    $edition = salon();

    $accueil = creneau($edition, position: 1, capacity: 4);
    $billetterie = creneau($edition, position: 2, capacity: 2, restricted: true);

    $accueil->mission->update(['name' => 'Accueil exposants']);
    $billetterie->mission->update(['name' => 'Billetterie']);

    Assignment::factory()->create(['user_id' => benevole($edition)->id, 'shift_id' => $accueil->id]);
    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => benevole($edition)->id,
        'shift_id' => $billetterie->id,
    ]);

    $byMission = app(EditionOverview::class)->fillRateByMission($edition)->keyBy('label');

    expect($byMission->get('Accueil exposants'))->toMatchArray(['taken' => 1, 'capacity' => 4, 'restricted' => false])
        ->and($byMission->get('Billetterie'))->toMatchArray(['taken' => 1, 'capacity' => 2, 'restricted' => true]);
});

it('ne compte ni les creneaux ni les benevoles d une autre edition', function () {
    $edition = salon();
    $autre = salon();

    InvitationCode::factory()->create(['edition_id' => $autre->id]);
    User::factory()->forEdition($autre)->validatedPlanning()->create();

    $creneauVoisin = creneau($autre, position: 1, capacity: 4);
    Assignment::factory()->create(['user_id' => benevole($autre)->id, 'shift_id' => $creneauVoisin->id]);

    $overview = app(EditionOverview::class);

    expect($overview->headcount($edition))->toMatchArray(['expected' => 0, 'accounts' => 0, 'validated' => 0])
        ->and($overview->fillRate($edition))->toMatchArray(['capacity' => 0, 'taken' => 0, 'rate' => 0]);
});

it('tient une jauge vide sans diviser par zero', function () {
    $edition = salon();

    Shift::factory()->withCapacity(0)->create([
        'edition_id' => $edition->id,
        'time_slot_id' => $edition->timeSlots()->where('position', 1)->value('id'),
    ]);

    expect(app(EditionOverview::class)->fillRate($edition))
        ->toMatchArray(['capacity' => 0, 'taken' => 0, 'rate' => 0, 'level' => 'full']);
});

it('affiche les compteurs sur la vue d ensemble', function () {
    $edition = salon();

    InvitationCode::factory()->count(4)->create(['edition_id' => $edition->id]);
    User::factory()->forEdition($edition)->validatedPlanning()->create();
    benevole($edition);

    $shift = creneau($edition, position: 1, capacity: 4);
    $shift->mission->update(['name' => 'Accueil exposants']);
    Assignment::factory()->create(['user_id' => benevole($edition)->id, 'shift_id' => $shift->id]);

    $this->actingAs(administrateur($edition))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Bénévoles attendus')
        ->assertSee('Comptes créés')
        ->assertSee('Plannings validés')
        ->assertSee('Plannings non validés')
        ->assertSee('Accueil exposants')
        ->assertSee('25 % de remplissage')
        ->assertSee('Vendredi 14 mai');
});
