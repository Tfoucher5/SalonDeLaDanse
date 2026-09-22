<?php

use App\Models\User;

it('affiche la page profil', function () {
    $this->actingAs(User::factory()->create())
        ->get('/profile')
        ->assertOk();
});

it('reduit la page profil au mot de passe pour un profil verrouille', function () {
    $response = $this->actingAs(User::factory()->create())->get('/profile');

    $response->assertOk()
        ->assertSee('name="current_password"', escape: false)
        ->assertDontSee('name="first_name"', escape: false)
        ->assertDontSee('name="email"', escape: false);
});

it('refuse toute modification des informations personnelles sur un profil verrouille', function () {
    $user = User::factory()->create([
        'first_name' => 'Marie',
        'email' => 'marie@example.test',
    ]);

    $this->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Pirate',
            'last_name' => 'Durand',
            'email' => 'pirate@example.test',
            'phone' => '0612345678',
        ])
        ->assertForbidden();

    $user->refresh();

    expect($user->first_name)->toBe('Marie')
        ->and($user->email)->toBe('marie@example.test');
});

it('laisse un profil non verrouille modifier ses informations', function () {
    $user = User::factory()->create(['profile_locked_at' => null]);

    $this->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Marie',
            'last_name' => 'Durand',
            'email' => 'marie.durand@example.test',
            'phone' => '06 12 34 56 78',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->full_name)->toBe('Marie Durand')
        ->and($user->email)->toBe('marie.durand@example.test')
        ->and($user->email_verified_at)->toBeNull();
});

it('laisse un administrateur modifier ses informations malgre le verrou', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch('/profile', [
            'first_name' => 'Claire',
            'last_name' => 'Martin',
            'email' => 'claire.martin@example.test',
            'phone' => '0612345678',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    expect($admin->refresh()->full_name)->toBe('Claire Martin');
});

it('ne change pas le statut de verification quand l e-mail est inchange', function () {
    $user = User::factory()->create(['profile_locked_at' => null]);

    $this->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Marie',
            'last_name' => 'Durand',
            'email' => $user->email,
            'phone' => '0612345678',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('ne propose plus au benevole de supprimer son compte', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertDontSee('profile.destroy');

    $this->actingAs($user)->delete('/profile', ['password' => 'password'])
        ->assertStatus(405);

    expect($user->fresh())->not->toBeNull();
});
