<?php

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * Le planning global du back-office, et la validation définitive.
 *
 * Deux exigences se croisent ici : l'administrateur doit voir nommément qui
 * occupe chaque créneau, et le bénévole ne doit jamais voir la même chose.
 */
beforeEach(function () {
    $this->edition = salon(['min_slots_per_volunteer' => 2]);
    $this->admin = administrateur($this->edition);

    $this->accueil = creneau($this->edition, position: 1, date: '2027-05-14', capacity: 4);
    $this->accueil->mission->update(['name' => 'Accueil exposants']);

    $this->billetterie = creneau($this->edition, position: 3, date: '2027-05-15', capacity: 2, restricted: true);
    $this->billetterie->mission->update(['name' => 'Billetterie']);

    $this->camille = User::factory()->forEdition($this->edition)->create([
        'first_name' => 'Camille', 'last_name' => 'Dorel',
    ]);
    Assignment::factory()->create(['user_id' => $this->camille->id, 'shift_id' => $this->accueil->id]);

    $this->naim = User::factory()->forEdition($this->edition)->create([
        'first_name' => 'Naim', 'last_name' => 'Belkacem',
    ]);
    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $this->naim->id, 'shift_id' => $this->billetterie->id,
    ]);
});

function planning(array $criteria = []): TestResponse
{
    return test()->actingAs(test()->admin)->get(route('admin.planning', $criteria));
}

it('nomme les benevoles inscrits sur chaque creneau du jour', function () {
    planning(['day' => '2027-05-14'])
        ->assertOk()
        ->assertSee('Accueil exposants')
        ->assertSee('Camille Dorel')
        ->assertSee('08:30 - 10:00')
        // Un autre jour, d'autres créneaux : Naim n'est pas du vendredi.
        ->assertDontSee('Naim Belkacem');

    planning(['day' => '2027-05-15'])
        ->assertOk()
        ->assertSee('Billetterie')
        ->assertSee('Naim Belkacem')
        ->assertDontSee('Camille Dorel');
});

it('montre les missions restreintes et distingue ce que l equipe a pose', function () {
    planning(['day' => '2027-05-15'])
        ->assertOk()
        ->assertSee('Billetterie')
        ->assertSee('Restreinte')
        ->assertSee('Équipe');
});

it('dit ce qui reste a pourvoir, et ce que personne n occupe', function () {
    $desert = creneau($this->edition, position: 5, date: '2027-05-14', capacity: 3);
    $desert->mission->update(['name' => 'Zone logistique']);

    planning(['day' => '2027-05-14'])
        ->assertOk()
        // Accueil : 1 place prise sur 4, donc 3 restantes, en toutes lettres.
        ->assertSee('3 places restantes')
        ->assertSee('Zone logistique')
        // Texte statique de la vue : Blade ne l'échappe pas, `assertSee` si.
        ->assertSee("Personne n'est inscrit sur ce créneau", escape: false);
});

it('ouvre sur le premier jour et retombe dessus si le jour demande est farfelu', function () {
    planning()->assertOk()->assertSee('Camille Dorel');
    planning(['day' => '2030-01-01'])->assertOk()->assertSee('Camille Dorel');
});

it('filtre par mission sans perdre le jour', function () {
    $response = planning(['day' => '2027-05-14', 'mission' => $this->accueil->mission_id])->assertOk();

    $response->assertSee('Accueil exposants')->assertSee('Camille Dorel');

    // Le filtre survit au changement de jour proposé par les onglets. Le « & »
    // qui sépare les paramètres sort échappé dans l'attribut `href`.
    $response->assertSee(
        e(route('admin.planning', ['day' => '2027-05-15', 'mission' => $this->accueil->mission_id])),
        escape: false,
    );
});

it('recherche un benevole par son nom sur la journee', function () {
    $autre = creneau($this->edition, position: 2, date: '2027-05-14', capacity: 4);
    $autre->mission->update(['name' => 'Vestiaires']);

    $response = planning(['day' => '2027-05-14', 'name' => 'dorel'])
        ->assertOk()
        ->assertSee('Camille Dorel')
        // La recherche survit au changement de jour.
        ->assertSee(e(route('admin.planning', ['day' => '2027-05-15', 'name' => 'dorel'])), escape: false);

    // « Vestiaires » reste proposee dans la liste des missions, mais sa carte
    // de creneau disparait : personne n'y porte ce nom.
    expect($response->getContent())->not->toMatch('/<h3[^>]*>\s*Vestiaires/');
});

it('recherche une mission par son nom', function () {
    planning(['day' => '2027-05-15', 'name' => 'billet'])
        ->assertOk()
        ->assertSee('Billetterie')
        ->assertSee('Naim Belkacem');

    planning(['day' => '2027-05-15', 'name' => 'accueil'])
        ->assertOk()
        ->assertDontSee('Naim Belkacem')
        ->assertSee('Aucun créneau ne correspond à la recherche');
});

it('mene de chaque nom a la fiche du benevole', function () {
    planning(['day' => '2027-05-14'])
        ->assertOk()
        ->assertSee(route('admin.volunteers.show', $this->camille), escape: false);
});

it('ne laisse aucun benevole atteindre le planning global', function () {
    $this->get(route('admin.planning'))->assertRedirect('/login');
    $this->actingAs($this->camille)->get(route('admin.planning'))->assertForbidden();

    // Et sa propre grille continue de ne montrer que des places. Camille lit son
    // propre nom dans la navigation : la fuite à traquer est celle d'un tiers
    // inscrit sur le même créneau qu'elle.
    $elio = User::factory()->forEdition($this->edition)->create([
        'first_name' => 'Elio', 'last_name' => 'Vasseur',
    ]);
    Assignment::factory()->create(['user_id' => $elio->id, 'shift_id' => $this->accueil->id]);

    $this->actingAs($this->camille)
        ->get(route('planning.index', ['day' => '2027-05-14']))
        ->assertOk()
        ->assertSee('2 places restantes')
        ->assertDontSee('Elio Vasseur')
        ->assertDontSee('Naim Belkacem');
});

it('valide definitivement un planning depuis la fiche', function () {
    Assignment::factory()->create([
        'user_id' => $this->camille->id,
        'shift_id' => creneau($this->edition, position: 3, date: '2027-05-14')->id,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.volunteers.show', $this->camille))
        ->post(route('admin.volunteers.validate', $this->camille))
        ->assertRedirect(route('admin.volunteers.show', $this->camille))
        ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'validé'));

    expect($this->camille->fresh()->planningIsValidated())->toBeTrue();
});

it('refuse de valider un planning sous le quota minimum', function () {
    // Camille n'a qu'un créneau, le minimum est de deux.
    $this->actingAs($this->admin)
        ->from(route('admin.volunteers.show', $this->camille))
        ->post(route('admin.volunteers.validate', $this->camille))
        ->assertSessionHasErrors('planning');

    expect($this->camille->fresh()->planningIsValidated())->toBeFalse();
});

it('valide meme une fois les inscriptions fermees', function () {
    // C'est le cas normal : l'organisation fige les plannings après la clôture.
    Assignment::factory()->create([
        'user_id' => $this->camille->id,
        'shift_id' => creneau($this->edition, position: 3, date: '2027-05-14')->id,
    ]);
    $this->edition->update(['is_locked' => true]);

    $this->actingAs($this->admin)
        ->from(route('admin.volunteers.show', $this->camille))
        ->post(route('admin.volunteers.validate', $this->camille))
        ->assertSessionHasNoErrors();

    expect($this->camille->fresh()->planningIsValidated())->toBeTrue();
});

it('valide un planning que l equipe a elle-meme verrouille', function () {
    // Naim porte une attribution forcée : elle ne doit pas bloquer la
    // validation, sinon l'administrateur se bloque lui-même.
    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $this->naim->id,
        'shift_id' => creneau($this->edition, position: 1, date: '2027-05-16')->id,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.volunteers.show', $this->naim))
        ->post(route('admin.volunteers.validate', $this->naim))
        ->assertSessionHasNoErrors();

    expect($this->naim->fresh()->planningIsValidated())->toBeTrue();
});

it('ne valide pas deux fois', function () {
    $this->camille->forceFill(['planning_validated_at' => now()->subDay()])->save();
    $validatedAt = $this->camille->fresh()->planning_validated_at;

    $this->actingAs($this->admin)
        ->from(route('admin.volunteers.show', $this->camille))
        ->post(route('admin.volunteers.validate', $this->camille))
        ->assertSessionHasErrors('planning');

    expect($this->camille->fresh()->planning_validated_at->eq($validatedAt))->toBeTrue();
});

it('offre la validation sur la fiche, et jamais au benevole', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.volunteers.show', $this->camille))
        ->assertOk()
        ->assertSee('Valider définitivement');

    $this->actingAs($this->camille)
        ->get(route('planning.index'))
        ->assertOk()
        ->assertDontSee('Valider définitivement');
});

it('ferme la validation au benevole', function () {
    $this->actingAs($this->camille)
        ->post(route('admin.volunteers.validate', $this->naim))
        ->assertForbidden();

    expect($this->naim->fresh()->planningIsValidated())->toBeFalse();
});
