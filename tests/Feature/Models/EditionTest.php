<?php

use App\Models\Edition;

it('expose les jours de l evenement, bornes incluses', function () {
    $edition = Edition::factory()->create([
        'starts_on' => '2027-05-14',
        'ends_on' => '2027-05-16',
    ]);

    expect($edition->days()->map->toDateString()->all())
        ->toBe(['2027-05-14', '2027-05-15', '2027-05-16']);
});

it('ouvre les inscriptions quand aucune borne n est posee', function () {
    expect(Edition::factory()->create()->registrationIsOpen())->toBeTrue();
});

it('ferme les inscriptions avant la date d ouverture', function () {
    $edition = Edition::factory()->create([
        'registration_opens_at' => now()->addDay(),
    ]);

    expect($edition->registrationIsOpen())->toBeFalse();
});

it('ferme les inscriptions apres la date de fermeture', function () {
    expect(Edition::factory()->registrationClosed()->create()->registrationIsOpen())->toBeFalse();
});

it('ouvre les inscriptions a l interieur de la fenetre', function () {
    $edition = Edition::factory()->create([
        'registration_opens_at' => now()->subDay(),
        'registration_closes_at' => now()->addDay(),
    ]);

    expect($edition->registrationIsOpen())->toBeTrue();
});

it('laisse le verrouillage manuel primer sur la fenetre', function () {
    $edition = Edition::factory()->locked()->create([
        'registration_opens_at' => now()->subDay(),
        'registration_closes_at' => now()->addDay(),
    ]);

    expect($edition->registrationIsOpen())->toBeFalse();
});

it('retourne l edition active comme edition courante', function () {
    Edition::factory()->create(['name' => 'Ancienne edition', 'is_active' => false]);
    $active = Edition::factory()->create(['name' => 'Salon de la Danse 2027']);

    expect(Edition::current()->id)->toBe($active->id);
});
