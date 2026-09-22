<?php

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
