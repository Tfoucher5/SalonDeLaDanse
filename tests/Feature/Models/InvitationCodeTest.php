<?php

use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Database\QueryException;

it('genere un code lisible et sans caractere ambigu', function () {
    $code = InvitationCode::generateCode();

    expect($code)->toStartWith('SALON-')
        ->and($code)->toMatch('/^SALON-[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/');
});

it('considere un code neuf comme disponible', function () {
    $code = InvitationCode::factory()->create();

    expect($code->isUsed())->toBeFalse()
        ->and(InvitationCode::query()->available()->count())->toBe(1);
});

it('sort un code consomme de la liste des codes disponibles', function () {
    $user = User::factory()->create();
    $code = InvitationCode::factory()->used($user)->create();

    expect($code->isUsed())->toBeTrue()
        ->and($code->user->is($user))->toBeTrue()
        ->and(InvitationCode::query()->available()->count())->toBe(0);
});

it('interdit deux codes identiques', function () {
    $code = InvitationCode::factory()->create();

    InvitationCode::factory()->create(['code' => $code->code]);
})->throws(QueryException::class);
