<?php

use App\Models\Edition;
use App\Models\User;

it('redirige un visiteur anonyme vers la connexion', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('affiche les dates, les quotas et les regles d engagement', function () {
    $edition = Edition::factory()->create([
        'min_slots_per_volunteer' => 1,
        'max_slots_per_volunteer' => 3,
    ]);

    $response = $this->actingAs(User::factory()->forEdition($edition)->create())->get('/dashboard');

    $response->assertOk()
        ->assertSee('Salon de la Danse 2027')
        ->assertSee('Vendredi')
        ->assertSee('14 mai 2027')
        ->assertSee('16 mai 2027')
        ->assertSee('de 1 à 3')
        ->assertSee('1 créneau minimum et 3 créneaux maximum');
});

it('formule les regles d engagement a partir des quotas de l edition', function () {
    $edition = Edition::factory()->create([
        'min_slots_per_volunteer' => 2,
        'max_slots_per_volunteer' => 5,
    ]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/dashboard')
        ->assertSee('2 créneau minimum et 5 créneaux maximum')
        ->assertDontSee('1 créneau minimum et 3 créneaux maximum');
});

it('affiche les coordonnees de l equipe organisatrice', function () {
    config()->set('salon.contact', [
        'name' => 'Pole benevoles JayDance Fam',
        'email' => 'benevoles@example.test',
        'phone' => '02 41 00 00 00',
    ]);

    Edition::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertSee('Pole benevoles JayDance Fam')
        ->assertSee('benevoles@example.test')
        ->assertSee('02 41 00 00 00');
});

it('ne laisse pas de coordonnees a vide quand rien n est renseigne', function () {
    config()->set('salon.contact', ['name' => null, 'email' => null, 'phone' => null]);

    Edition::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee("Les coordonnées de l'équipe organisatrice ne sont pas encore renseignées.", escape: false);
});

it('retombe sur l edition courante pour un compte sans rattachement', function () {
    $edition = Edition::factory()->create(['name' => 'Salon de la Danse 2027']);

    $this->actingAs(User::factory()->create(['edition_id' => null]))
        ->get('/dashboard')
        ->assertOk()
        ->assertSee($edition->name);
});

it('reste consultable sans aucune edition en base', function () {
    $this->actingAs(User::factory()->create(['edition_id' => null]))
        ->get('/dashboard')
        ->assertOk()
        ->assertSee("Aucune édition n'est ouverte pour le moment.", escape: false);
});

it('ne divulgue le nom d aucun autre benevole', function () {
    $edition = Edition::factory()->create();
    $autre = User::factory()->forEdition($edition)->create(['last_name' => 'Duchesneau']);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/dashboard')
        ->assertDontSee($autre->last_name)
        ->assertDontSee($autre->email);
});
