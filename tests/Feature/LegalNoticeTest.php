<?php

use App\Models\User;

it('publie les mentions legales sans connexion', function () {
    $this->get(route('legal.notice'))
        ->assertOk()
        ->assertSee('JayDance Fam')
        ->assertSee('W491019569')
        ->assertSee('877 993 584')
        ->assertSee('Données personnelles');
});

it('affiche l hebergeur renseigne dans la configuration', function () {
    config(['salon.legal.host' => ['name' => 'Hébergeur Test', 'address' => '1 rue du Test, 75000 Paris', 'website' => null]]);

    $this->get(route('legal.notice'))
        ->assertOk()
        ->assertSee('Hébergeur Test')
        ->assertSee('1 rue du Test, 75000 Paris')
        ->assertDontSee('seront publiées ici');
});

it('signale un hebergeur non renseigne plutot que d afficher des cases vides', function () {
    config(['salon.legal.host' => ['name' => null, 'address' => null, 'website' => null]]);

    $this->get(route('legal.notice'))
        ->assertOk()
        ->assertSee('seront publiées ici');
});

it('ramene un benevole connecte vers son tableau de bord', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('legal.notice'))
        ->assertSee(route('dashboard'))
        ->assertSee('Tableau de bord');
});

it('relie les mentions legales depuis le pied de page', function () {
    $this->get('/')->assertSee(route('legal.notice'));
});
