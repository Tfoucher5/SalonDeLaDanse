<?php

use App\Enums\UserRole;
use App\Http\Middleware\RequireValidatedInvitationCode;
use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->edition = Edition::factory()->create();
    $this->code = InvitationCode::factory()->create(['edition_id' => $this->edition->id]);

    $this->withSession([RequireValidatedInvitationCode::SESSION_KEY => $this->code->code]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Marie',
        'last_name' => 'Durand',
        'email' => 'marie.durand@example.test',
        'phone' => '06 12 34 56 78',
        'birth_date' => '1990-05-14',
        'photo' => fakePhoto(),
        'password' => 'mot-de-passe-solide',
        'password_confirmation' => 'mot-de-passe-solide',
    ], $overrides);
}

it('affiche le formulaire d inscription une fois le code valide', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('name="birth_date"', escape: false)
        ->assertSee('Aperçu de votre photo');
});

it('cree le compte benevole, consomme le code et stocke la photo', function () {
    $response = $this->post(route('register'), registrationPayload());

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'marie.durand@example.test')->sole();

    expect($user->full_name)->toBe('Marie Durand')
        ->and($user->phone)->toBe('06 12 34 56 78')
        ->and($user->birth_date->toDateString())->toBe('1990-05-14')
        ->and($user->role)->toBe(UserRole::Volunteer)
        ->and($user->edition_id)->toBe($this->edition->id)
        ->and($user->profileIsLocked())->toBeTrue()
        ->and($user->photo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($user->photo_path);

    $code = $this->code->fresh();

    expect($code->isUsed())->toBeTrue()
        ->and($code->user_id)->toBe($user->id);

    // Le code est retire de la session : il ne peut plus resservir.
    expect(session()->has(RequireValidatedInvitationCode::SESSION_KEY))->toBeFalse();
});

it('hache le mot de passe', function () {
    $this->post(route('register'), registrationPayload());

    $user = User::query()->sole();

    expect($user->password)->not->toBe('mot-de-passe-solide')
        ->and(Hash::check('mot-de-passe-solide', $user->password))->toBeTrue();
});

it('refuse une inscription sans code valide en session', function () {
    $this->flushSession();

    $this->post(route('register'), registrationPayload())
        ->assertRedirect(route('register.code'));

    expect(User::count())->toBe(0);
});

it('refuse un code consomme entre l affichage et l envoi du formulaire', function () {
    $this->code->forceFill([
        'used_at' => now(),
        'user_id' => User::factory()->create()->id,
    ])->save();

    $this->post(route('register'), registrationPayload())
        ->assertRedirect(route('register.code'))
        ->assertSessionHasErrors('code');

    expect(User::query()->where('email', 'marie.durand@example.test')->exists())->toBeFalse();
});

it('ne laisse pas de photo orpheline quand l inscription echoue', function () {
    $this->code->forceFill([
        'used_at' => now(),
        'user_id' => User::factory()->create()->id,
    ])->save();

    $this->post(route('register'), registrationPayload());

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('ne permet pas de reutiliser un code deja consomme par soi-meme', function () {
    $this->post(route('register'), registrationPayload())->assertSessionHasNoErrors();

    $this->post('/logout');

    $this->withSession([RequireValidatedInvitationCode::SESSION_KEY => $this->code->code])
        ->post(route('register'), registrationPayload(['email' => 'autre@example.test']))
        ->assertRedirect(route('register.code'));

    expect(User::count())->toBe(1);
});

it('exige une photo', function () {
    $this->post(route('register'), registrationPayload(['photo' => null]))
        ->assertSessionHasErrors('photo');

    expect(User::count())->toBe(0);
});

it('refuse un fichier qui n est pas une image', function () {
    $this->post(route('register'), registrationPayload([
        'photo' => UploadedFile::fake()->create('planning.pdf', 100, 'application/pdf'),
    ]))->assertSessionHasErrors('photo');
});

it('refuse une photo trop lourde', function () {
    config()->set('salon.photo.max_kilobytes', 100);

    $this->post(route('register'), registrationPayload([
        'photo' => fakePhoto(paddingKilobytes: 150),
    ]))->assertSessionHasErrors('photo');
});

it('exige prenom, nom, e-mail, telephone et date de naissance', function (string $field) {
    $this->post(route('register'), registrationPayload([$field => '']))
        ->assertSessionHasErrors($field);

    expect(User::count())->toBe(0);
})->with(['first_name', 'last_name', 'email', 'phone', 'birth_date']);

it('refuse un telephone au format inattendu', function () {
    $this->post(route('register'), registrationPayload(['phone' => 'appelez-moi']))
        ->assertSessionHasErrors('phone');
});

it('refuse une adresse e-mail deja utilisee', function () {
    User::factory()->create(['email' => 'marie.durand@example.test']);

    $this->post(route('register'), registrationPayload())
        ->assertSessionHasErrors('email');
});

it('refuse une date de naissance dans le futur', function () {
    $this->post(route('register'), registrationPayload([
        'birth_date' => today()->addDay()->toDateString(),
    ]))->assertSessionHasErrors('birth_date');

    expect(User::count())->toBe(0);
});

it('refuse une date de naissance qui n est pas une date', function () {
    $this->post(route('register'), registrationPayload(['birth_date' => '14 mai']))
        ->assertSessionHasErrors('birth_date');
});

it('demande de choisir de nouveau la photo apres un refus', function () {
    $this->from(route('register'))
        ->followingRedirects()
        ->post(route('register'), registrationPayload(['email' => 'pas-un-email']))
        ->assertOk()
        ->assertSee('choisissez de nouveau votre photo');
});
