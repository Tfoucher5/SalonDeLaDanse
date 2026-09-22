<?php

use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\Mission;
use App\Models\Shift;
use App\Models\TimeSlot;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    // Les identifiants admin viennent de .env : on les fixe ici pour que le test
    // ne depende pas de la machine qui l execute.
    config()->set('salon.admin.email', 'admin@example.test');
    config()->set('salon.admin.password', 'mot-de-passe-de-test');
    config()->set('salon.seed.default_shift_capacity', 4);
    config()->set('salon.seed.invitation_codes', 20);

    $this->seed(DatabaseSeeder::class);
});

it('seede une seule edition active', function () {
    expect(Edition::count())->toBe(1);

    $edition = Edition::current();

    expect($edition->name)->toBe('Salon de la Danse 2027')
        ->and($edition->starts_on->toDateString())->toBe('2027-05-14')
        ->and($edition->ends_on->toDateString())->toBe('2027-05-16')
        ->and($edition->min_slots_per_volunteer)->toBe(1)
        ->and($edition->max_slots_per_volunteer)->toBe(3)
        ->and($edition->days())->toHaveCount(3);
});

it('seede les cinq tranches horaires dans l ordre', function () {
    $slots = Edition::current()->timeSlots()->get();

    expect($slots)->toHaveCount(5)
        ->and($slots->pluck('position')->all())->toBe([1, 2, 3, 4, 5]);

    expect($slots->first()->label())->toBe('08:30 - 10:00')
        ->and($slots->last()->label())->toBe('16:00 - 18:00');
});

it('seede neuf missions publiques et deux missions restreintes', function () {
    expect(Mission::count())->toBe(11)
        ->and(Mission::query()->public()->count())->toBe(9)
        ->and(Mission::query()->restricted()->count())->toBe(2);

    expect(Mission::query()->restricted()->pluck('name')->sort()->values()->all())
        ->toBe(['Billetterie', 'Caisse']);
});

it('seede 135 creneaux publics reservables', function () {
    expect(Shift::query()->onPublicMissions()->count())->toBe(135)
        ->and(Shift::count())->toBe(165);
});

it('repartit les creneaux sur les trois jours et les cinq tranches', function () {
    expect(Shift::query()->distinct()->pluck('date')->count())->toBe(3)
        ->and(Shift::query()->distinct()->pluck('time_slot_id')->count())->toBe(5);
});

it('donne a chaque creneau la jauge par defaut', function () {
    expect(Shift::query()->where('capacity', 4)->count())->toBe(Shift::count());
});

it('cree le compte administrateur a partir de la configuration', function () {
    $admin = User::query()->admins()->sole();

    expect($admin->email)->toBe('admin@example.test')
        ->and($admin->isAdmin())->toBeTrue()
        ->and($admin->profile_locked_at)->not->toBeNull()
        ->and($admin->edition_id)->toBe(Edition::current()->id);
});

it('ne cree pas de compte administrateur sans identifiants configures', function () {
    User::query()->admins()->delete();
    config()->set('salon.admin.email', null);
    config()->set('salon.admin.password', null);

    $this->seed(AdminSeeder::class);

    expect(User::query()->admins()->count())->toBe(0);
});

it('seede un lot de codes d invitation disponibles et uniques', function () {
    $codes = InvitationCode::all();

    expect($codes)->toHaveCount(20)
        ->and($codes->pluck('code')->unique())->toHaveCount(20)
        ->and(InvitationCode::query()->available()->count())->toBe(20);
});

it('rattache toutes les donnees de reference a l edition courante', function () {
    $edition = Edition::current();

    expect(TimeSlot::query()->where('edition_id', '!=', $edition->id)->count())->toBe(0)
        ->and(Mission::query()->where('edition_id', '!=', $edition->id)->count())->toBe(0)
        ->and(Shift::query()->where('edition_id', '!=', $edition->id)->count())->toBe(0);
});

it('peut etre rejoue sans dupliquer les donnees de reference', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Edition::count())->toBe(1)
        ->and(Mission::count())->toBe(11)
        ->and(TimeSlot::count())->toBe(5)
        ->and(Shift::count())->toBe(165)
        ->and(InvitationCode::count())->toBe(20);
});
