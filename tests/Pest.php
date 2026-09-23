<?php

use App\Models\Edition;
use App\Models\Mission;
use App\Models\Shift;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\PlanningRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Une photo de test, valide aux yeux de la regle image.
 *
 * On n utilise pas UploadedFile::fake()->image() : cette fabrique exige
 * l extension GD, absente de certaines installations PHP et de la CI. Un PNG
 * 1x1 encode en dur rend le meme service sans dependance.
 *
 * @param  int  $paddingKilobytes  Octets de remplissage, pour tester la regle de poids.
 */
function fakePhoto(string $name = 'portrait.png', int $paddingKilobytes = 0): UploadedFile
{
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    );

    if ($paddingKilobytes > 0) {
        // Les octets ajoutes apres le marqueur de fin sont ignores par les
        // decodeurs PNG : le fichier reste une image, il pese seulement plus lourd.
        $png .= str_repeat("\0", $paddingKilobytes * 1024);
    }

    return UploadedFile::fake()->createWithContent($name, $png);
}

/**
 * Une edition ouverte a l inscription, avec ses cinq tranches horaires.
 *
 * @param  array<string, mixed>  $attributes
 */
function salon(array $attributes = []): Edition
{
    $edition = Edition::factory()->create($attributes + [
        'registration_opens_at' => now()->subDay(),
        'registration_closes_at' => now()->addDay(),
    ]);

    $tranches = [
        1 => ['08:30', '10:00'],
        2 => ['10:00', '12:00'],
        3 => ['12:00', '14:00'],
        4 => ['14:00', '16:00'],
        5 => ['16:00', '18:00'],
    ];

    foreach ($tranches as $position => [$startsAt, $endsAt]) {
        TimeSlot::factory()->atPosition($position, $startsAt, $endsAt)->create([
            'edition_id' => $edition->id,
        ]);
    }

    return $edition;
}

/**
 * Un creneau reservable : une mission a lui, la tranche et le jour demandes.
 */
function creneau(
    Edition $edition,
    int $position = 1,
    string $date = '2027-05-14',
    int $capacity = 4,
    bool $restricted = false,
): Shift {
    $mission = Mission::factory()
        ->when($restricted, fn ($factory) => $factory->restricted())
        ->create(['edition_id' => $edition->id]);

    return Shift::factory()->withCapacity($capacity)->create([
        'edition_id' => $edition->id,
        'mission_id' => $mission->id,
        'time_slot_id' => $edition->timeSlots()->where('position', $position)->value('id'),
        'date' => $date,
    ]);
}

function benevole(Edition $edition): User
{
    return User::factory()->forEdition($edition)->create();
}

function rules(): PlanningRules
{
    return app(PlanningRules::class);
}
