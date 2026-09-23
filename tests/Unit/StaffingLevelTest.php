<?php

use App\Enums\StaffingLevel;

it('passe du rouge au vert a mesure qu un poste se remplit', function (int $taken, int $capacity, StaffingLevel $expected) {
    expect(StaffingLevel::fromCounts($taken, $capacity))->toBe($expected);
})->with([
    'vide' => [0, 4, StaffingLevel::Critical],
    'moins de la moitie' => [1, 4, StaffingLevel::Critical],
    'a moitie' => [2, 4, StaffingLevel::Partial],
    'presque complet' => [3, 4, StaffingLevel::Partial],
    'complet' => [4, 4, StaffingLevel::Staffed],
]);

it('juge le planning d un benevole a son quota', function (int $count, StaffingLevel $expected) {
    expect(StaffingLevel::forQuota($count, minimum: 1, maximum: 3))->toBe($expected);
})->with([
    'sous le minimum' => [0, StaffingLevel::Critical],
    'entre les deux' => [2, StaffingLevel::Partial],
    'au maximum' => [3, StaffingLevel::Staffed],
]);
