<?php

use App\Models\Assignment;
use App\Models\Mission;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\QueryException;

it('calcule les places restantes a partir des reservations', function () {
    $shift = Shift::factory()->withCapacity(3)->create();

    expect($shift->remaining_places)->toBe(3);

    Assignment::factory()->count(2)->create(['shift_id' => $shift->id]);

    expect($shift->fresh()->remaining_places)->toBe(1);
});

it('signale un creneau complet', function () {
    $shift = Shift::factory()->withCapacity(1)->create();
    Assignment::factory()->create(['shift_id' => $shift->id]);

    expect($shift->fresh()->isFull())->toBeTrue()
        ->and($shift->fresh()->remaining_places)->toBe(0);
});

it('ne descend jamais sous zero place restante', function () {
    $shift = Shift::factory()->withCapacity(1)->create();
    Assignment::factory()->count(3)->create(['shift_id' => $shift->id]);

    expect($shift->fresh()->remaining_places)->toBe(0);
});

it('utilise le compteur charge en eager loading quand il existe', function () {
    $shift = Shift::factory()->withCapacity(5)->create();
    Assignment::factory()->count(2)->create(['shift_id' => $shift->id]);

    $loaded = Shift::query()->withCount('assignments')->find($shift->id);

    expect($loaded->remaining_places)->toBe(3);
});

it('ne retient que les creneaux des missions publiques', function () {
    $public = Shift::factory()->create();
    Shift::factory()->create([
        'mission_id' => Mission::factory()->restricted()->create()->id,
    ]);

    expect(Shift::count())->toBe(2)
        ->and(Shift::query()->onPublicMissions()->pluck('id')->all())->toBe([$public->id]);
});

it('interdit deux creneaux identiques sur mission, tranche et date', function () {
    $shift = Shift::factory()->create();

    Shift::factory()->create([
        'edition_id' => $shift->edition_id,
        'mission_id' => $shift->mission_id,
        'time_slot_id' => $shift->time_slot_id,
        'date' => $shift->date,
    ]);
})->throws(QueryException::class);

it('interdit deux reservations du meme benevole sur le meme creneau', function () {
    $user = User::factory()->create();
    $shift = Shift::factory()->create();

    Assignment::factory()->create(['user_id' => $user->id, 'shift_id' => $shift->id]);
    Assignment::factory()->create(['user_id' => $user->id, 'shift_id' => $shift->id]);
})->throws(QueryException::class);
