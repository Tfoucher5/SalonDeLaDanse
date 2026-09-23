<?php

use App\Exceptions\BookingRuleException;
use App\Models\Assignment;
use App\Models\Shift;
use App\Models\User;

it('redirige un visiteur anonyme vers la connexion', function () {
    $shift = creneau(salon());

    $this->post(route('planning.shifts.store', $shift))->assertRedirect('/login');
});

it('ajoute un creneau au planning en brouillon', function () {
    $edition = salon();
    $shift = creneau($edition);
    $user = benevole($edition);

    $this->actingAs($user)
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $shift))
        ->assertRedirect(route('planning.index'))
        ->assertSessionHas('status', 'Créneau ajouté à votre planning.');

    $this->assertDatabaseHas('assignments', [
        'user_id' => $user->id,
        'shift_id' => $shift->id,
        'assigned_by_admin' => false,
    ]);
});

it('retire un creneau du planning en brouillon', function () {
    $edition = salon();
    $shift = creneau($edition);
    $user = benevole($edition);
    Assignment::factory()->create(['user_id' => $user->id, 'shift_id' => $shift->id]);

    $this->actingAs($user)
        ->from(route('planning.index'))
        ->delete(route('planning.shifts.destroy', $shift))
        ->assertRedirect(route('planning.index'))
        ->assertSessionHas('status', 'Créneau retiré de votre planning.');

    $this->assertDatabaseMissing('assignments', [
        'user_id' => $user->id,
        'shift_id' => $shift->id,
    ]);
});

it('refuse de retirer un creneau qui n a pas ete retenu', function () {
    $edition = salon();
    $shift = creneau($edition);
    $autre = benevole($edition);
    Assignment::factory()->create(['user_id' => $autre->id, 'shift_id' => $shift->id]);

    $this->actingAs(benevole($edition))
        ->from(route('planning.index'))
        ->delete(route('planning.shifts.destroy', $shift))
        ->assertSessionHasErrors(['shift' => 'Ce créneau ne fait pas partie de votre planning.']);

    $this->assertDatabaseCount('assignments', 1);
});

it('refuse un creneau deja retenu', function () {
    $edition = salon();
    $shift = creneau($edition);
    $user = benevole($edition);
    Assignment::factory()->create(['user_id' => $user->id, 'shift_id' => $shift->id]);

    $this->actingAs($user)
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $shift))
        ->assertSessionHasErrors(['shift' => 'Ce créneau fait déjà partie de votre planning.']);

    $this->assertDatabaseCount('assignments', 1);
});

it('refuse un quatrieme creneau quand le quota maximum est atteint', function () {
    $edition = salon(['max_slots_per_volunteer' => 3]);
    $user = benevole($edition);

    foreach ([1, 3, 5] as $position) {
        Assignment::factory()->create([
            'user_id' => $user->id,
            'shift_id' => creneau($edition, position: $position)->id,
        ]);
    }

    $quatrieme = creneau($edition, position: 1, date: '2027-05-15');

    $this->actingAs($user)
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $quatrieme))
        ->assertSessionHasErrors([
            'shift' => 'Vous avez déjà retenu vos 3 créneaux. Retirez-en un pour en choisir un autre.',
        ]);

    $this->assertDatabaseCount('assignments', 3);
});

it('refuse deux missions sur la meme tranche horaire du meme jour', function () {
    $edition = salon();
    $user = benevole($edition);

    Assignment::factory()->create([
        'user_id' => $user->id,
        'shift_id' => creneau($edition, position: 2, date: '2027-05-14')->id,
    ]);

    $concurrent = creneau($edition, position: 2, date: '2027-05-14');

    $this->actingAs($user)
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $concurrent))
        ->assertSessionHasErrors([
            'shift' => 'Vous êtes déjà inscrit sur une autre mission de cette tranche horaire.',
        ]);

    $this->assertDatabaseCount('assignments', 1);
});

it('accepte la meme tranche horaire sur deux jours differents', function () {
    $edition = salon();
    $user = benevole($edition);

    Assignment::factory()->create([
        'user_id' => $user->id,
        'shift_id' => creneau($edition, position: 2, date: '2027-05-14')->id,
    ]);

    $lendemain = creneau($edition, position: 2, date: '2027-05-15');

    $this->actingAs($user)
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $lendemain))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseCount('assignments', 2);
});

it('refuse trois tranches horaires consecutives le meme jour', function () {
    $edition = salon();
    $user = benevole($edition);

    foreach ([2, 3] as $position) {
        Assignment::factory()->create([
            'user_id' => $user->id,
            'shift_id' => creneau($edition, position: $position, date: '2027-05-14')->id,
        ]);
    }

    $troisieme = creneau($edition, position: 4, date: '2027-05-14');

    $this->actingAs($user)
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $troisieme))
        ->assertSessionHasErrors([
            'shift' => 'Vous ne pouvez pas enchaîner trois tranches horaires consécutives le même jour : une pause est obligatoire.',
        ]);

    $this->assertDatabaseCount('assignments', 2);
});

it('accepte trois tranches du meme jour separees par une pause', function () {
    $edition = salon();
    $user = benevole($edition);

    foreach ([2, 3] as $position) {
        Assignment::factory()->create([
            'user_id' => $user->id,
            'shift_id' => creneau($edition, position: $position, date: '2027-05-14')->id,
        ]);
    }

    $apresPause = creneau($edition, position: 5, date: '2027-05-14');

    $this->actingAs($user)
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $apresPause))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseCount('assignments', 3);
});

it('accepte des tranches consecutives sur des jours differents', function () {
    $edition = salon();
    $user = benevole($edition);

    Assignment::factory()->create([
        'user_id' => $user->id,
        'shift_id' => creneau($edition, position: 2, date: '2027-05-14')->id,
    ]);
    Assignment::factory()->create([
        'user_id' => $user->id,
        'shift_id' => creneau($edition, position: 3, date: '2027-05-15')->id,
    ]);

    $troisieme = creneau($edition, position: 4, date: '2027-05-16');

    $this->actingAs($user)
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $troisieme))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseCount('assignments', 3);
});

it('refuse un creneau dont la jauge est atteinte', function () {
    $edition = salon();
    $shift = creneau($edition, capacity: 1);
    Assignment::factory()->create(['shift_id' => $shift->id]);

    $this->actingAs(benevole($edition))
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $shift))
        ->assertSessionHasErrors(['shift' => 'Toutes les places de ce créneau sont prises.']);

    $this->assertDatabaseCount('assignments', 1);
});

it('refuse une mission sous restriction, jamais offerte au benevole', function () {
    $edition = salon();
    $billetterie = creneau($edition, restricted: true);

    $this->actingAs(benevole($edition))
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $billetterie))
        ->assertSessionHasErrors([
            'shift' => "Cette mission est attribuée uniquement par l'équipe organisatrice.",
        ]);

    $this->assertDatabaseCount('assignments', 0);
});

it('refuse un creneau appartenant a une autre edition', function () {
    $edition = salon();
    $ailleurs = creneau(salon(['is_active' => false]));

    $this->actingAs(benevole($edition))
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $ailleurs))
        ->assertSessionHasErrors(['shift' => "Ce créneau n'appartient pas à l'édition en cours."]);

    $this->assertDatabaseCount('assignments', 0);
});

it('barre la route d ajout hors de la fenetre d inscription', function () {
    $edition = salon(['registration_closes_at' => now()->subDay()]);
    $shift = creneau($edition);

    $this->actingAs(benevole($edition))
        ->post(route('planning.shifts.store', $shift))
        ->assertForbidden();

    $this->assertDatabaseCount('assignments', 0);
});

it('refuse un ajout hors de la fenetre d inscription', function () {
    $edition = salon(['registration_closes_at' => now()->subDay()]);
    $shift = creneau($edition);

    expect(fn () => rules()->book(benevole($edition), $shift))
        ->toThrow(BookingRuleException::class, 'Les inscriptions au planning sont fermées.');
});

it('refuse un ajout quand l edition est verrouillee manuellement', function () {
    $edition = salon(['is_locked' => true]);
    $shift = creneau($edition);

    expect(fn () => rules()->book(benevole($edition), $shift))
        ->toThrow(BookingRuleException::class, 'Les inscriptions au planning sont fermées.');
});

it('refuse un ajout quand le planning est valide definitivement', function () {
    $edition = salon();
    $shift = creneau($edition);
    $user = User::factory()->forEdition($edition)->validatedPlanning()->create();

    expect(fn () => rules()->book($user, $shift))
        ->toThrow(BookingRuleException::class, 'Votre planning est validé définitivement.');
});

it('refuse un retrait quand le planning est valide definitivement', function () {
    $edition = salon();
    $shift = creneau($edition);
    $user = User::factory()->forEdition($edition)->validatedPlanning()->create();
    Assignment::factory()->create(['user_id' => $user->id, 'shift_id' => $shift->id]);

    expect(fn () => rules()->release($user, $shift))
        ->toThrow(BookingRuleException::class, 'Votre planning est validé définitivement.');

    $this->assertDatabaseCount('assignments', 1);
});

it('n attribue la derniere place qu a un seul benevole, meme sur une lecture perimee', function () {
    $edition = salon();
    $shift = creneau($edition, capacity: 1);

    // Le second benevole a charge la grille quand la place etait encore libre :
    // son modele est perime, le service doit relire la jauge en base.
    $perime = Shift::query()->find($shift->id);

    rules()->book(benevole($edition), $shift);

    expect(fn () => rules()->book(benevole($edition), $perime))
        ->toThrow(BookingRuleException::class, 'Toutes les places de ce créneau sont prises.');

    expect(Assignment::where('shift_id', $shift->id)->count())->toBe(1);
});

it('annule l ajout quand la derniere place est prise pendant l ecriture', function () {
    $edition = salon();
    $shift = creneau($edition, capacity: 1);
    $user = benevole($edition);
    $concurrent = benevole($edition);

    // Une base SQLite en memoire n autorise pas deux connexions simultanees : on
    // reproduit l instant precis ou un concurrent s intercale entre le controle
    // de la jauge et l insertion. Le recomptage doit annuler la transaction.
    $intercale = false;

    Assignment::created(function (Assignment $assignment) use ($shift, $concurrent, &$intercale): void {
        if ($intercale || $assignment->shift_id !== $shift->id) {
            return;
        }

        $intercale = true;

        Assignment::create(['user_id' => $concurrent->id, 'shift_id' => $shift->id]);
    });

    expect(fn () => rules()->book($user, $shift))
        ->toThrow(BookingRuleException::class, 'Toutes les places de ce créneau sont prises.');

    expect($intercale)->toBeTrue()
        ->and(Assignment::count())->toBe(0);
});

it('verrouille le planning des qu un poste est attribue par l equipe organisatrice', function () {
    $edition = salon();
    $user = benevole($edition);

    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $user->id,
        'shift_id' => creneau($edition, position: 1, restricted: true)->id,
    ]);

    expect(fn () => rules()->book($user, creneau($edition, position: 3)))
        ->toThrow(BookingRuleException::class, "Votre planning est verrouillé : l'équipe organisatrice vous a attribué un poste.");

    $this->assertDatabaseCount('assignments', 1);
});

it('refuse le retrait d un poste attribue par l equipe organisatrice', function () {
    $edition = salon();
    $user = benevole($edition);
    $billetterie = creneau($edition, restricted: true);

    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $user->id,
        'shift_id' => $billetterie->id,
    ]);

    expect(fn () => rules()->release($user, $billetterie))
        ->toThrow(BookingRuleException::class, "Votre planning est verrouillé : l'équipe organisatrice vous a attribué un poste.");

    $this->assertDatabaseCount('assignments', 1);
});

it('verrouille aussi les creneaux que le benevole avait choisis lui-meme', function () {
    $edition = salon();
    $user = benevole($edition);
    $choisi = creneau($edition, position: 1);

    Assignment::factory()->create(['user_id' => $user->id, 'shift_id' => $choisi->id]);
    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $user->id,
        'shift_id' => creneau($edition, position: 3, restricted: true)->id,
    ]);

    expect(fn () => rules()->release($user->fresh(), $choisi))
        ->toThrow(BookingRuleException::class, "Votre planning est verrouillé : l'équipe organisatrice vous a attribué un poste.");

    $this->assertDatabaseCount('assignments', 2);
});

it('barre la route d ajout quand un poste a ete attribue par l equipe organisatrice', function () {
    $edition = salon();
    $user = benevole($edition);

    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $user->id,
        'shift_id' => creneau($edition, position: 1, restricted: true)->id,
    ]);

    $this->actingAs($user)
        ->post(route('planning.shifts.store', creneau($edition, position: 3)))
        ->assertForbidden();

    $this->assertDatabaseCount('assignments', 1);
});

it('laisse le planning en brouillon sur un creneau choisi par le benevole', function () {
    $edition = salon();
    $user = benevole($edition);

    Assignment::factory()->create([
        'user_id' => $user->id,
        'shift_id' => creneau($edition, position: 1)->id,
    ]);

    $this->actingAs($user)
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', creneau($edition, position: 3)))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseCount('assignments', 2);
});
