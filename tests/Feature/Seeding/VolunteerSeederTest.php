<?php

use App\Models\Assignment;
use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\VolunteerSeeder;

beforeEach(function () {
    config()->set('salon.admin.email', null);
    config()->set('salon.seed.volunteers', 40);

    $this->seed(DatabaseSeeder::class);
    $this->seed(VolunteerSeeder::class);

    $this->volunteers = User::query()
        ->where('email', 'like', '%@'.VolunteerSeeder::EMAIL_DOMAIN)
        ->with('shifts.timeSlot', 'shifts.mission')
        ->get();
});

it('cree des benevoles inscrits avec un code d invitation consomme', function () {
    expect($this->volunteers)->toHaveCount(40);

    $this->volunteers->each(function (User $volunteer): void {
        expect($volunteer->edition_id)->toBe(Edition::current()->id)
            ->and(InvitationCode::query()->where('user_id', $volunteer->id)->whereNotNull('used_at')->count())->toBe(1);
    });
});

it('respecte le quota maximum, les chevauchements et les missions restreintes', function () {
    $maximum = Edition::current()->max_slots_per_volunteer;

    expect(Assignment::count())->toBeGreaterThan(0);

    $this->volunteers->each(function (User $volunteer) use ($maximum): void {
        $shifts = $volunteer->shifts;

        expect($shifts->count())->toBeLessThanOrEqual($maximum)
            ->and($shifts->every(fn ($shift): bool => $shift->mission->is_public))->toBeTrue();

        $slotsPerDay = $shifts->groupBy(fn ($shift): string => $shift->date->toDateString());

        $slotsPerDay->each(function ($dayShifts): void {
            $positions = $dayShifts->map(fn ($shift): int => $shift->timeSlot->position)->sort()->values();

            // Jamais deux missions sur la meme tranche, jamais trois tranches d affilee.
            expect($positions->unique()->count())->toBe($positions->count());

            for ($i = 2; $i < $positions->count(); $i++) {
                expect($positions[$i] - $positions[$i - 2])->not->toBe(2);
            }
        });
    });
});

it('ne depasse jamais la jauge d un creneau', function () {
    Assignment::query()->with('shift')->get()->groupBy('shift_id')->each(
        fn ($assignments) => expect($assignments->count())->toBeLessThanOrEqual($assignments->first()->shift->capacity)
    );
});

it('ne valide que des plannings qui atteignent le minimum', function () {
    $minimum = Edition::current()->min_slots_per_volunteer;

    $this->volunteers
        ->filter(fn (User $volunteer): bool => $volunteer->planning_validated_at !== null)
        ->each(fn (User $volunteer) => expect($volunteer->shifts->count())->toBeGreaterThanOrEqual($minimum));
});

it('peut etre rejoue sans creer de doublons', function () {
    $this->seed(VolunteerSeeder::class);

    expect(User::query()->where('email', 'like', '%@'.VolunteerSeeder::EMAIL_DOMAIN)->count())->toBe(40);
});
