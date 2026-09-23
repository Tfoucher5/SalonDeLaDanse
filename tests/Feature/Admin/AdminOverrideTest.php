<?php

use App\Models\Assignment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/**
 * La main de l'administrateur : ce qu'il outrepasse, et le peu qui lui résiste.
 *
 * Chaque règle du lot 4 est reprise une par une — elle doit mordre sur le
 * bénévole, et céder devant le back-office. Les deux seuls refus qui subsistent
 * ne sont pas des règles du Salon mais des incohérences de données.
 */
beforeEach(function () {
    $this->edition = salon(['max_slots_per_volunteer' => 3]);
    $this->admin = administrateur($this->edition);
    $this->volunteer = benevole($this->edition);
});

/**
 * L'administrateur attribue un créneau au bénévole du test.
 */
function attribue(Shift $shift, ?User $volunteer = null): TestResponse
{
    $volunteer ??= test()->volunteer;

    return test()->actingAs(test()->admin)
        ->from(route('admin.volunteers.show', $volunteer))
        ->post(route('admin.volunteers.shifts.store', $volunteer), ['shift_id' => $shift->id]);
}

it('force une mission restreinte et marque l attribution', function () {
    $billetterie = creneau($this->edition, position: 1, restricted: true);
    $billetterie->mission->update(['name' => 'Billetterie']);

    attribue($billetterie)->assertRedirect(route('admin.volunteers.show', $this->volunteer));

    $assignment = Assignment::query()->where('user_id', $this->volunteer->id)->sole();

    expect($assignment->shift_id)->toBe($billetterie->id)
        ->and($assignment->assigned_by_admin)->toBeTrue();

    // Le bénévole, lui, se heurte toujours à la même mission.
    expect(rules()->violationFor(benevole($this->edition), $billetterie)?->value)
        ->toBe('restricted_mission');
});

it('nomme la regle qu il vient d outrepasser', function () {
    $complet = creneau($this->edition, position: 1, capacity: 1);
    Assignment::factory()->create(['user_id' => benevole($this->edition)->id, 'shift_id' => $complet->id]);

    attribue($complet)->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'outrepasse une règle')
        && str_contains($status, 'complet'));
});

it('outrepasse le quota maximum de creneaux', function () {
    foreach ([1, 3, 5] as $position) {
        Assignment::factory()->create([
            'user_id' => $this->volunteer->id,
            'shift_id' => creneau($this->edition, position: $position)->id,
        ]);
    }

    $quatrieme = creneau($this->edition, position: 2, date: '2027-05-15');

    attribue($quatrieme);

    expect($this->volunteer->assignments()->count())->toBe(4)
        ->toBeGreaterThan($this->edition->max_slots_per_volunteer);
});

it('outrepasse une jauge complete', function () {
    $complet = creneau($this->edition, position: 1, capacity: 1);
    Assignment::factory()->create(['user_id' => benevole($this->edition)->id, 'shift_id' => $complet->id]);

    expect($complet->fresh()->isFull())->toBeTrue();

    attribue($complet);

    expect($complet->assignments()->count())->toBe(2);
});

it('outrepasse le chevauchement de deux missions sur la meme tranche', function () {
    $premier = creneau($this->edition, position: 2, date: '2027-05-14');
    $concurrent = creneau($this->edition, position: 2, date: '2027-05-14');

    Assignment::factory()->create(['user_id' => $this->volunteer->id, 'shift_id' => $premier->id]);

    attribue($concurrent);

    expect($this->volunteer->assignments()->count())->toBe(2);
});

it('outrepasse l interdiction de trois tranches consecutives', function () {
    foreach ([1, 2] as $position) {
        Assignment::factory()->create([
            'user_id' => $this->volunteer->id,
            'shift_id' => creneau($this->edition, position: $position, date: '2027-05-14')->id,
        ]);
    }

    $troisieme = creneau($this->edition, position: 3, date: '2027-05-14');

    attribue($troisieme);

    expect($this->volunteer->assignments()->count())->toBe(3);
});

it('ecrit sur un planning deja valide', function () {
    $this->volunteer->forceFill(['planning_validated_at' => now()])->save();

    $shift = creneau($this->edition, position: 1);

    attribue($shift);

    expect($this->volunteer->assignments()->count())->toBe(1)
        ->and($this->volunteer->fresh()->planningIsValidated())->toBeTrue();
});

it('ecrit alors que la fenetre d inscription est fermee', function () {
    $this->edition->update(['is_locked' => true]);

    attribue(creneau($this->edition, position: 1));

    expect($this->volunteer->assignments()->count())->toBe(1);
});

it('refuse les deux seules incoherences qui restent', function () {
    $shift = creneau($this->edition, position: 1);
    Assignment::factory()->create(['user_id' => $this->volunteer->id, 'shift_id' => $shift->id]);

    // Deux fois le même créneau : ce n'est pas une règle du Salon, c'est un doublon.
    attribue($shift)->assertSessionHasErrors('shift_id');

    $autre = salon();
    attribue(creneau($autre, position: 1))->assertSessionHasErrors('shift_id');

    expect($this->volunteer->assignments()->count())->toBe(1);
});

it('retire un creneau d un planning verrouille', function () {
    $shift = creneau($this->edition, position: 1);
    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $this->volunteer->id,
        'shift_id' => $shift->id,
    ]);
    $this->volunteer->forceFill(['planning_validated_at' => now()])->save();

    $this->actingAs($this->admin)
        ->from(route('admin.volunteers.show', $this->volunteer))
        ->delete(route('admin.volunteers.shifts.destroy', [$this->volunteer, $shift]))
        ->assertRedirect(route('admin.volunteers.show', $this->volunteer))
        ->assertSessionHas('status');

    expect($this->volunteer->assignments()->count())->toBe(0);
});

it('rouvre un planning valide', function () {
    $this->volunteer->forceFill(['planning_validated_at' => now()])->save();

    $this->actingAs($this->admin)
        ->from(route('admin.volunteers.show', $this->volunteer))
        ->post(route('admin.volunteers.unlock', $this->volunteer))
        ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'rouvert'));

    expect($this->volunteer->fresh()->planningIsValidated())->toBeFalse();
});

it('previent qu une attribution forcee verrouille encore le planning rouvert', function () {
    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $this->volunteer->id,
        'shift_id' => creneau($this->edition, position: 1, restricted: true)->id,
    ]);
    $this->volunteer->forceFill(['planning_validated_at' => now()])->save();

    $this->actingAs($this->admin)
        ->from(route('admin.volunteers.show', $this->volunteer))
        ->post(route('admin.volunteers.unlock', $this->volunteer))
        ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'reste verrouillé'));

    $reloaded = $this->volunteer->fresh();

    expect($reloaded->planningIsValidated())->toBeFalse()
        ->and($reloaded->planningIsLockedByAdmin())->toBeTrue();
});

it('laisse le benevole sans prise sur un creneau que l administrateur lui a pose', function () {
    $billetterie = creneau($this->edition, position: 1, restricted: true);

    attribue($billetterie);

    // L'attribution verrouille le planning : le bénévole n'atteint même plus
    // la route d'écriture, le middleware de fenêtre le coupe avant.
    $this->actingAs($this->volunteer)
        ->from(route('planning.index'))
        ->delete(route('planning.shifts.destroy', $billetterie))
        ->assertForbidden();

    expect($this->volunteer->assignments()->count())->toBe(1);
});

it('reinitialise le mot de passe et affiche celui a transmettre', function () {
    $previousToken = $this->volunteer->remember_token;

    $response = $this->actingAs($this->admin)
        ->from(route('admin.volunteers.show', $this->volunteer))
        ->post(route('admin.volunteers.credentials', $this->volunteer))
        ->assertSessionHas('temporary_password');

    $temporary = $response->getSession()->get('temporary_password');
    $reloaded = $this->volunteer->fresh();

    expect(Hash::check($temporary, $reloaded->password))->toBeTrue()
        ->and(Hash::check('password', $reloaded->password))->toBeFalse()
        ->and($reloaded->remember_token)->not->toBe($previousToken);

    // Le mot de passe temporaire ouvre bien une session.
    $this->post(route('logout'));
    $this->post(route('login'), ['email' => $reloaded->email, 'password' => $temporary])
        ->assertRedirect(route('dashboard', absolute: false));
});

it('modifie les informations personnelles verrouillees', function () {
    $this->volunteer->forceFill(['profile_locked_at' => now()])->save();

    $this->actingAs($this->admin)
        ->patch(route('admin.volunteers.update', $this->volunteer), [
            'first_name' => 'Solene',
            'last_name' => 'Marchand',
            'email' => 'Solene.Marchand@example.test',
            'phone' => '06 12 34 56 78',
            'birth_date' => '1998-04-12',
        ])
        ->assertRedirect(route('admin.volunteers.show', $this->volunteer));

    $reloaded = $this->volunteer->fresh();

    expect($reloaded->full_name)->toBe('Solene Marchand')
        // L'adresse est normalisée, et redevient à vérifier : c'est l'identifiant de connexion.
        ->and($reloaded->email)->toBe('solene.marchand@example.test')
        ->and($reloaded->email_verified_at)->toBeNull()
        ->and($reloaded->birth_date->toDateString())->toBe('1998-04-12');
});

it('refuse une adresse deja prise par un autre compte', function () {
    $autre = benevole($this->edition);

    $this->actingAs($this->admin)
        ->patch(route('admin.volunteers.update', $this->volunteer), [
            'first_name' => 'Solene',
            'last_name' => 'Marchand',
            'email' => $autre->email,
            'phone' => '0612345678',
            'birth_date' => '1998-04-12',
        ])
        ->assertSessionHasErrors('email');

    expect($this->volunteer->fresh()->email)->not->toBe($autre->email);
});

it('remplace la photo sans laisser l ancienne sur le disque', function () {
    Storage::fake('public');

    $ancienne = fakePhoto('ancienne.png')->store(config('salon.photo.directory'), 'public');
    $this->volunteer->forceFill(['photo_path' => $ancienne])->save();

    $this->actingAs($this->admin)
        ->patch(route('admin.volunteers.update', $this->volunteer), [
            'first_name' => $this->volunteer->first_name,
            'last_name' => $this->volunteer->last_name,
            'email' => $this->volunteer->email,
            'phone' => '0612345678',
            'birth_date' => '1998-04-12',
            'photo' => fakePhoto('nouvelle.png'),
        ])
        ->assertSessionHasNoErrors();

    $reloaded = $this->volunteer->fresh();

    expect($reloaded->photo_path)->not->toBe($ancienne);

    Storage::disk('public')->assertExists($reloaded->photo_path);
    Storage::disk('public')->assertMissing($ancienne);
});

it('conserve la photo quand aucune nouvelle n est deposee', function () {
    Storage::fake('public');

    $photo = fakePhoto('portrait.png')->store(config('salon.photo.directory'), 'public');
    $this->volunteer->forceFill(['photo_path' => $photo])->save();

    $this->actingAs($this->admin)
        ->patch(route('admin.volunteers.update', $this->volunteer), [
            'first_name' => 'Solene',
            'last_name' => $this->volunteer->last_name,
            'email' => $this->volunteer->email,
            'phone' => '0612345678',
            'birth_date' => '1998-04-12',
        ])
        ->assertSessionHasNoErrors();

    expect($this->volunteer->fresh()->photo_path)->toBe($photo);
    Storage::disk('public')->assertExists($photo);
});

it('ferme tout l outrepassement au benevole', function () {
    $shift = creneau($this->edition, position: 1);
    Assignment::factory()->create(['user_id' => $this->volunteer->id, 'shift_id' => $shift->id]);

    $cible = benevole($this->edition);

    $this->actingAs($this->volunteer)->get(route('admin.volunteers.edit', $cible))->assertForbidden();
    $this->actingAs($this->volunteer)->patch(route('admin.volunteers.update', $cible))->assertForbidden();
    $this->actingAs($this->volunteer)->post(route('admin.volunteers.credentials', $cible))->assertForbidden();
    $this->actingAs($this->volunteer)->post(route('admin.volunteers.unlock', $cible))->assertForbidden();
    $this->actingAs($this->volunteer)->post(route('admin.volunteers.shifts.store', $cible))->assertForbidden();
    $this->actingAs($this->volunteer)
        ->delete(route('admin.volunteers.shifts.destroy', [$this->volunteer, $shift]))
        ->assertForbidden();

    expect($this->volunteer->assignments()->count())->toBe(1);
});

it('offre les leviers d outrepassement sur la fiche', function () {
    $this->volunteer->forceFill(['planning_validated_at' => now()])->save();

    Assignment::factory()->create([
        'user_id' => $this->volunteer->id,
        'shift_id' => creneau($this->edition, position: 1)->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.volunteers.show', $this->volunteer))
        ->assertOk()
        ->assertSee('Attribuer un créneau')
        ->assertSee('Réinitialiser le mot de passe')
        ->assertSee('Rouvrir le planning')
        ->assertSee(route('admin.volunteers.edit', $this->volunteer), escape: false);
});

it('propose les missions restreintes a l attribution, jamais au benevole', function () {
    $billetterie = creneau($this->edition, position: 1, restricted: true);
    $billetterie->mission->update(['name' => 'Billetterie']);

    $this->actingAs($this->admin)
        ->get(route('admin.volunteers.show', $this->volunteer))
        ->assertOk()
        ->assertSee('Billetterie (restreinte)');

    $this->actingAs($this->volunteer)
        ->get(route('planning.index'))
        ->assertOk()
        ->assertDontSee('Billetterie');
});
