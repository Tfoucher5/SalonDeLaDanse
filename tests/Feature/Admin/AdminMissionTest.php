<?php

use App\Models\Assignment;
use App\Models\Mission;
use App\Models\Shift;
use App\Services\MissionCatalogue;

/**
 * Le catalogue des missions, et surtout ses deux refus.
 *
 * Régler une jauge et fermer une mission touchent à des inscriptions déjà
 * prises : ce sont ces collisions qui sont testées ici, pas le remplissage
 * d'un formulaire.
 */
beforeEach(function () {
    $this->edition = salon();
    $this->admin = administrateur($this->edition);
});

/**
 * Le corps complet du formulaire, dont on ne surcharge que ce qui compte.
 */
function missionPayload(array $overrides = []): array
{
    return [
        'name' => 'Zone logistique',
        'default_capacity' => 4,
        'position' => 3,
        'instructions' => null,
        'is_public' => '1',
        'is_active' => '1',
        ...$overrides,
    ];
}

it('cree une mission et lui ouvre un creneau par jour et par tranche', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.missions.store'), missionPayload(['default_capacity' => 6]))
        ->assertRedirect(route('admin.missions.index'))
        ->assertSessionHasNoErrors();

    $mission = Mission::query()->where('name', 'Zone logistique')->sole();

    // 3 jours x 5 tranches.
    expect($mission->shifts()->count())->toBe(15)
        ->and($mission->shifts()->pluck('capacity')->unique()->all())->toBe([6])
        ->and($mission->slug)->toBe('zone-logistique');
});

it('refuse deux missions du meme nom dans une edition', function () {
    Mission::factory()->create(['edition_id' => $this->edition->id, 'name' => 'Zone logistique']);

    $this->actingAs($this->admin)
        ->post(route('admin.missions.store'), missionPayload())
        ->assertSessionHasErrors('name');

    expect(Mission::query()->where('name', 'Zone logistique')->count())->toBe(1);
});

it('applique la jauge de la mission a tous ses creneaux', function () {
    $shift = creneau($this->edition, position: 1, capacity: 4);
    $autre = Shift::factory()->withCapacity(4)->create([
        'edition_id' => $this->edition->id,
        'mission_id' => $shift->mission_id,
        'time_slot_id' => $this->edition->timeSlots()->where('position', 2)->value('id'),
        'date' => '2027-05-15',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.missions.update', $shift->mission), missionPayload([
            'name' => $shift->mission->name,
            'default_capacity' => 9,
        ]))
        ->assertRedirect(route('admin.missions.index'))
        ->assertSessionHasNoErrors();

    expect($shift->fresh()->capacity)->toBe(9)
        ->and($autre->fresh()->capacity)->toBe(9)
        ->and($shift->mission->fresh()->default_capacity)->toBe(9);
});

it('refuse une jauge inferieure a ce qui est deja attribue', function () {
    $shift = creneau($this->edition, position: 1, capacity: 4);
    $shift->mission->update(['name' => 'Accueil exposants']);

    foreach (range(1, 3) as $ignored) {
        Assignment::factory()->create([
            'user_id' => benevole($this->edition)->id,
            'shift_id' => $shift->id,
        ]);
    }

    $response = $this->actingAs($this->admin)
        ->from(route('admin.missions.edit', $shift->mission))
        ->patch(route('admin.missions.update', $shift->mission), missionPayload([
            'name' => 'Accueil exposants',
            'default_capacity' => 2,
        ]))
        ->assertSessionHasErrors('default_capacity');

    // Le message dit ou aller retirer quelqu'un, pas seulement « impossible ».
    $error = $response->getSession()->get('errors')->first('default_capacity');

    expect($error)->toContain('14 mai')
        ->toContain('08:30 - 10:00')
        ->toContain('3 inscrits')
        ->toContain('au moins égale à 3');

    // Rien n'a bouge : ni la mission, ni ses creneaux.
    expect($shift->fresh()->capacity)->toBe(4)
        ->and($shift->mission->fresh()->default_capacity)->toBe(4);
});

it('accepte une jauge egale au nombre de personnes deja inscrites', function () {
    $shift = creneau($this->edition, position: 1, capacity: 4);

    foreach (range(1, 2) as $ignored) {
        Assignment::factory()->create([
            'user_id' => benevole($this->edition)->id,
            'shift_id' => $shift->id,
        ]);
    }

    $this->actingAs($this->admin)
        ->patch(route('admin.missions.update', $shift->mission), missionPayload([
            'name' => $shift->mission->name,
            'default_capacity' => 2,
        ]))
        ->assertSessionHasNoErrors();

    expect($shift->fresh()->capacity)->toBe(2);
});

it('ne compte que les creneaux de la mission visee dans le conflit de jauge', function () {
    $mission = creneau($this->edition, position: 1, capacity: 4);
    $voisine = creneau($this->edition, position: 2, capacity: 4);

    foreach (range(1, 4) as $ignored) {
        Assignment::factory()->create([
            'user_id' => benevole($this->edition)->id,
            'shift_id' => $voisine->id,
        ]);
    }

    expect(app(MissionCatalogue::class)->capacityConflicts($mission->mission, 1))->toBeEmpty()
        ->and(app(MissionCatalogue::class)->capacityConflicts($voisine->mission, 1))->toHaveCount(1);
});

it('ferme une mission sans defaire ce qui y est attribue', function () {
    $shift = creneau($this->edition, position: 1);
    $volunteer = benevole($this->edition);
    Assignment::factory()->create(['user_id' => $volunteer->id, 'shift_id' => $shift->id]);

    $this->actingAs($this->admin)
        ->patch(route('admin.missions.update', $shift->mission), missionPayload([
            'name' => $shift->mission->name,
            'is_active' => '0',
        ]))
        ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'gardent leur poste'));

    expect($shift->mission->fresh()->is_active)->toBeFalse()
        ->and($volunteer->assignments()->count())->toBe(1);
});

it('retire une mission fermee de la grille du benevole', function () {
    $ouverte = creneau($this->edition, position: 1);
    $ouverte->mission->update(['name' => 'Accueil exposants']);

    $fermee = creneau($this->edition, position: 2, closed: true);
    $fermee->mission->update(['name' => 'Zone logistique']);

    $this->actingAs(benevole($this->edition))
        ->get(route('planning.index', ['day' => '2027-05-14']))
        ->assertOk()
        ->assertSee('Accueil exposants')
        ->assertDontSee('Zone logistique');
});

it('refuse la reservation d une mission fermee, meme forcee par la route', function () {
    $fermee = creneau($this->edition, position: 1, closed: true);
    $volunteer = benevole($this->edition);

    $this->actingAs($volunteer)
        ->from(route('planning.index'))
        ->post(route('planning.shifts.store', $fermee))
        ->assertSessionHasErrors('shift');

    expect($volunteer->assignments()->count())->toBe(0);
});

it('laisse le benevole voir le creneau d une mission fermee apres coup', function () {
    $shift = creneau($this->edition, position: 1);
    $shift->mission->update(['name' => 'Zone logistique']);

    $volunteer = benevole($this->edition);
    Assignment::factory()->create(['user_id' => $volunteer->id, 'shift_id' => $shift->id]);

    $shift->mission->update(['is_active' => false]);

    // Son créneau ne doit pas s'évaporer de la grille sans explication.
    $this->actingAs($volunteer)
        ->get(route('planning.index', ['day' => '2027-05-14']))
        ->assertOk()
        ->assertSee('Zone logistique');
});

it('laisse l administrateur attribuer une mission fermee, en le lui disant', function () {
    $fermee = creneau($this->edition, position: 1, closed: true);
    $volunteer = benevole($this->edition);

    $this->actingAs($this->admin)
        ->from(route('admin.volunteers.show', $volunteer))
        ->post(route('admin.volunteers.shifts.store', $volunteer), ['shift_id' => $fermee->id])
        ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'désactivée'));

    expect($volunteer->assignments()->count())->toBe(1);
});

it('refuse de supprimer une mission sur laquelle des benevoles sont inscrits', function () {
    $shift = creneau($this->edition, position: 1);
    $shift->mission->update(['name' => 'Accueil exposants']);
    Assignment::factory()->create(['user_id' => benevole($this->edition)->id, 'shift_id' => $shift->id]);

    $this->actingAs($this->admin)
        ->from(route('admin.missions.edit', $shift->mission))
        ->delete(route('admin.missions.destroy', $shift->mission))
        ->assertSessionHasErrors('mission');

    expect(Mission::query()->whereKey($shift->mission_id)->exists())->toBeTrue()
        ->and(Shift::query()->whereKey($shift->id)->exists())->toBeTrue();
});

it('supprime une mission vide et ses creneaux', function () {
    $shift = creneau($this->edition, position: 1);
    $mission = $shift->mission;

    $this->actingAs($this->admin)
        ->delete(route('admin.missions.destroy', $mission))
        ->assertRedirect(route('admin.missions.index'))
        ->assertSessionHasNoErrors();

    expect(Mission::query()->whereKey($mission->id)->exists())->toBeFalse()
        ->and(Shift::query()->whereKey($shift->id)->exists())->toBeFalse();
});

it('liste les missions avec leur jauge et ce qui y est attribue', function () {
    $shift = creneau($this->edition, position: 1, capacity: 6);
    $shift->mission->update(['name' => 'Accueil exposants']);
    Assignment::factory()->create(['user_id' => benevole($this->edition)->id, 'shift_id' => $shift->id]);

    creneau($this->edition, position: 2, closed: true)->mission->update(['name' => 'Zone logistique']);

    $this->actingAs($this->admin)
        ->get(route('admin.missions.index'))
        ->assertOk()
        ->assertSee('Accueil exposants')
        ->assertSee('6 / créneau')
        ->assertSee('Zone logistique')
        ->assertSee('Fermée');
});

it('affiche les deux formulaires du catalogue', function () {
    $shift = creneau($this->edition, position: 1, capacity: 5);
    $shift->mission->update(['name' => 'Accueil exposants']);
    Assignment::factory()->create(['user_id' => benevole($this->edition)->id, 'shift_id' => $shift->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.missions.create'))
        ->assertOk()
        ->assertSee('Nombre de personnes par créneau')
        ->assertSee('Mission active');

    $this->actingAs($this->admin)
        ->get(route('admin.missions.edit', $shift->mission))
        ->assertOk()
        ->assertSee('Accueil exposants')
        // L'administrateur doit savoir ce qui est déjà pourvu avant de toucher
        // à la jauge ou de fermer la mission.
        ->assertSee('Cette mission est déjà pourvue')
        ->assertSee('Supprimer la mission');
});

it('ferme le catalogue a tout le monde sauf a l administrateur', function () {
    $mission = Mission::factory()->create(['edition_id' => $this->edition->id]);
    $volunteer = benevole($this->edition);

    $this->get(route('admin.missions.index'))->assertRedirect('/login');

    $this->actingAs($volunteer)->get(route('admin.missions.index'))->assertForbidden();
    $this->actingAs($volunteer)->get(route('admin.missions.create'))->assertForbidden();
    $this->actingAs($volunteer)->post(route('admin.missions.store'), missionPayload())->assertForbidden();
    $this->actingAs($volunteer)->get(route('admin.missions.edit', $mission))->assertForbidden();
    $this->actingAs($volunteer)->patch(route('admin.missions.update', $mission), missionPayload())->assertForbidden();
    $this->actingAs($volunteer)->delete(route('admin.missions.destroy', $mission))->assertForbidden();

    expect(Mission::query()->whereKey($mission->id)->exists())->toBeTrue();
});
