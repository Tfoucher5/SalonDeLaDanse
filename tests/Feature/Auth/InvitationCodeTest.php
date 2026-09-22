<?php

use App\Http\Middleware\RequireValidatedInvitationCode;
use App\Models\InvitationCode;
use App\Models\User;

it('affiche l ecran de saisie du code', function () {
    $this->get(route('register.code'))->assertOk();
});

it('renvoie vers la saisie du code quand aucun code n a ete valide', function () {
    $this->get(route('register'))
        ->assertRedirect(route('register.code'))
        ->assertSessionHasErrors('code');
});

it('refuse un code absent', function () {
    $this->post(route('register.code'), ['code' => ''])
        ->assertSessionHasErrors('code');

    expect(session()->has(RequireValidatedInvitationCode::SESSION_KEY))->toBeFalse();
});

it('refuse un code inconnu', function () {
    $this->post(route('register.code'), ['code' => 'SALON-ZZZZZZ'])
        ->assertSessionHasErrors('code');

    expect(session()->has(RequireValidatedInvitationCode::SESSION_KEY))->toBeFalse();
});

it('refuse un code deja consomme', function () {
    $code = InvitationCode::factory()->used(User::factory()->create())->create();

    $this->post(route('register.code'), ['code' => $code->code])
        ->assertSessionHasErrors('code');

    expect(session()->has(RequireValidatedInvitationCode::SESSION_KEY))->toBeFalse();
});

it('accepte un code disponible et ouvre le formulaire', function () {
    $code = InvitationCode::factory()->create();

    $this->post(route('register.code'), ['code' => $code->code])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('register'));

    expect(session(RequireValidatedInvitationCode::SESSION_KEY))->toBe($code->code);

    $this->get(route('register'))->assertOk()->assertSee($code->code);
});

it('normalise la casse et les espaces du code saisi', function () {
    $code = InvitationCode::factory()->create();

    $this->post(route('register.code'), ['code' => '  '.strtolower($code->code).' '])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('register'));

    expect(session(RequireValidatedInvitationCode::SESSION_KEY))->toBe($code->code);
});

it('reprend la main si le code est consomme apres sa validation', function () {
    $code = InvitationCode::factory()->create();

    $this->withSession([RequireValidatedInvitationCode::SESSION_KEY => $code->code]);

    $code->forceFill(['used_at' => now(), 'user_id' => User::factory()->create()->id])->save();

    $this->get(route('register'))
        ->assertRedirect(route('register.code'))
        ->assertSessionHasErrors('code');
});
