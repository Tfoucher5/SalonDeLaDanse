<?php

use App\Enums\PlanningState;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Route factice portant le middleware, pour l eprouver sans dependre des
 * routes de planning qui n existent pas encore.
 */
beforeEach(function () {
    Route::middleware(['web', 'auth', 'registration.open'])
        ->get('/_test/planning', fn () => 'ouvert')
        ->name('test.planning');
});

it('laisse composer le planning dans la fenetre d inscription', function () {
    $edition = Edition::factory()->create([
        'registration_opens_at' => now()->subDay(),
        'registration_closes_at' => now()->addDay(),
    ]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/_test/planning')
        ->assertOk()
        ->assertSee('ouvert');
});

it('ferme la composition du planning hors des dates', function () {
    $edition = Edition::factory()->registrationClosed()->create();

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/_test/planning')
        ->assertForbidden();
});

it('ferme la composition du planning sur verrouillage manuel', function () {
    $edition = Edition::factory()->locked()->create([
        'registration_opens_at' => now()->subDay(),
        'registration_closes_at' => now()->addDay(),
    ]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/_test/planning')
        ->assertForbidden();
});

it('ferme la composition du planning apres validation definitive', function () {
    $edition = Edition::factory()->create();

    $user = User::factory()->forEdition($edition)->validatedPlanning()->create();

    $this->actingAs($user)->get('/_test/planning')->assertForbidden();
});

it('annonce un planning non valide quand la fenetre est ouverte', function () {
    $edition = Edition::factory()->create();

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/dashboard')
        ->assertSee('Non validé')
        ->assertDontSee('Brouillon')
        ->assertDontSee('consultation seule');
});

it('bascule le dashboard en consultation seule hors des dates', function () {
    $edition = Edition::factory()->registrationClosed()->create();

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/dashboard')
        ->assertSee('Inscriptions fermées')
        ->assertSee('consultation seule');
});

it('bascule le dashboard en consultation seule sur verrouillage manuel', function () {
    $edition = Edition::factory()->locked()->create();

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/dashboard')
        ->assertSee('Inscriptions fermées')
        ->assertSee('consultation seule');
});

it('annonce un planning valide, fenetre ouverte ou non', function () {
    $ouverte = Edition::factory()->create();
    $fermee = Edition::factory()->registrationClosed()->create(['is_active' => false]);

    foreach ([$ouverte, $fermee] as $edition) {
        $user = User::factory()->forEdition($edition)->validatedPlanning()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertSee('Validé')
            ->assertSee('consultation seule');
    }
});

it('considere le planning ferme quand aucune edition n existe', function () {
    $user = User::factory()->create(['edition_id' => null]);

    expect(PlanningState::for($user, null))->toBe(PlanningState::Closed)
        ->and(PlanningState::Closed->isEditable())->toBeFalse();
});
