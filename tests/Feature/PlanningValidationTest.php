<?php

use App\Enums\BookingRule;
use App\Exceptions\BookingRuleException;
use App\Models\Assignment;
use App\Models\Shift;
use App\Models\User;

/**
 * La validation definitive n est pas une action du benevole : elle appartient a
 * l equipe organisatrice, qui l appellera depuis le back-office. Ces tests
 * portent donc sur la regle elle-meme, et sur le verrouillage qu elle produit.
 */

/**
 * Retient un creneau pour ce benevole, sans passer par les regles metier :
 * ces tests portent sur la validation, pas sur la reservation.
 */
function retient(User $user, Shift $shift): Assignment
{
    return Assignment::factory()->create([
        'user_id' => $user->id,
        'shift_id' => $shift->id,
    ]);
}

it('refuse de valider un planning sous le quota minimum', function () {
    $edition = salon(['min_slots_per_volunteer' => 2]);
    $volunteer = benevole($edition);

    retient($volunteer, creneau($edition, position: 1));

    expect(rules()->validationViolationFor($volunteer))
        ->toBe(BookingRule::MinimumSlotsNotReached);

    expect(fn () => rules()->validate($volunteer))
        ->toThrow(BookingRuleException::class);

    expect($volunteer->fresh()->planningIsValidated())->toBeFalse();
});

it('refuse de valider un planning vide', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    expect(rules()->validationViolationFor($volunteer))
        ->toBe(BookingRule::MinimumSlotsNotReached);

    expect($volunteer->fresh()->planningIsValidated())->toBeFalse();
});

it('valide un planning qui atteint le quota minimum', function () {
    $edition = salon(['min_slots_per_volunteer' => 2]);
    $volunteer = benevole($edition);

    retient($volunteer, creneau($edition, position: 1));
    retient($volunteer, creneau($edition, position: 3));

    expect(rules()->validationViolationFor($volunteer))->toBeNull();

    rules()->validate($volunteer);

    expect($volunteer->fresh()->planningIsValidated())->toBeTrue();
});

it('fige la date de validation au premier passage', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    retient($volunteer, creneau($edition, position: 1));

    rules()->validate($volunteer);
    $validatedAt = $volunteer->fresh()->planning_validated_at;

    expect(fn () => rules()->validate($volunteer->fresh()))
        ->toThrow(BookingRuleException::class, BookingRule::PlanningValidated->message());

    expect($volunteer->fresh()->planning_validated_at->eq($validatedAt))->toBeTrue();
});

it('refuse toute modification du planning apres validation', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    $retenu = creneau($edition, position: 1);
    $libre = creneau($edition, position: 3);
    retient($volunteer, $retenu);
    rules()->validate($volunteer);

    $volunteer = $volunteer->fresh();

    $this->actingAs($volunteer)
        ->post(route('planning.shifts.store', $libre))
        ->assertForbidden();

    $this->actingAs($volunteer)
        ->delete(route('planning.shifts.destroy', $retenu))
        ->assertForbidden();

    expect($volunteer->assignments()->count())->toBe(1);
});

it('refuse de valider hors fenetre d inscription', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    retient($volunteer, creneau($edition, position: 1));
    $edition->update(['is_locked' => true]);

    expect(rules()->validationViolationFor($volunteer->fresh()))
        ->toBe(BookingRule::PlanningClosed);

    expect($volunteer->fresh()->planningIsValidated())->toBeFalse();
});

it('n offre aucune validation au benevole sur la grille', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    retient($volunteer, creneau($edition, position: 1));

    $this->actingAs($volunteer->fresh())
        ->get(route('planning.index'))
        ->assertOk()
        ->assertDontSee('Valider définitivement');
});
