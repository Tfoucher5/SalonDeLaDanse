<?php

use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\Shift;
use App\Models\User;

it('compose le nom complet a partir du prenom et du nom', function () {
    $user = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Durand']);

    expect($user->full_name)->toBe('Marie Durand');
});

it('caste le role en enum', function () {
    expect(User::factory()->create()->role)->toBe(UserRole::Volunteer)
        ->and(User::factory()->admin()->create()->role)->toBe(UserRole::Admin);
});

it('distingue benevole et administrateur', function () {
    $volunteer = User::factory()->create();
    $admin = User::factory()->admin()->create();

    expect($volunteer->isVolunteer())->toBeTrue()
        ->and($volunteer->isAdmin())->toBeFalse()
        ->and($admin->isAdmin())->toBeTrue()
        ->and($admin->isVolunteer())->toBeFalse();
});

it('filtre les benevoles et les administrateurs par scope', function () {
    User::factory()->count(3)->create();
    User::factory()->admin()->count(2)->create();

    expect(User::query()->volunteers()->count())->toBe(3)
        ->and(User::query()->admins()->count())->toBe(2);
});

it('verrouille le profil des la creation', function () {
    expect(User::factory()->create()->profileIsLocked())->toBeTrue();
});

it('distingue un planning en brouillon d un planning valide', function () {
    expect(User::factory()->create()->planningIsValidated())->toBeFalse()
        ->and(User::factory()->validatedPlanning()->create()->planningIsValidated())->toBeTrue();
});

it('n accepte pas le role depuis une affectation de masse', function () {
    $user = new User;
    $user->fill(['first_name' => 'Marie', 'last_name' => 'Durand', 'role' => UserRole::Admin]);

    expect($user->role)->not->toBe(UserRole::Admin);
});

it('relie un benevole a ses creneaux', function () {
    $user = User::factory()->create();
    $shift = Shift::factory()->create();
    Assignment::factory()->create(['user_id' => $user->id, 'shift_id' => $shift->id]);

    expect($user->assignments()->count())->toBe(1)
        ->and($user->shifts()->pluck('shifts.id')->all())->toBe([$shift->id]);
});
