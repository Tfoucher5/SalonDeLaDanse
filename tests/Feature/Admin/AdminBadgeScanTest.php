<?php

use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\Edition;
use App\Services\Badges\BadgePrinter;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;

/**
 * Le scanner de l'entrée : le verdict JSON qu'affiche la fenêtre de scan.
 *
 * Le code arrive tel que le QR code le porte — l'adresse signée de la page de
 * vérification —, ou tel qu'un administrateur le tape quand la caméra manque.
 */
beforeEach(function () {
    $this->edition = salon(['name' => 'Salon de la Danse 2027']);
    $this->admin = administrateur($this->edition);

    $this->volunteer = benevole($this->edition);
    $this->volunteer->update([
        'first_name' => 'Camille',
        'last_name' => 'Dorel',
        'email' => 'camille.dorel@example.test',
        'phone' => '0611223344',
    ]);

    $shift = creneau($this->edition, position: 2);
    $shift->mission->update(['name' => 'Accueil exposants']);
    Assignment::factory()->create(['user_id' => $this->volunteer->id, 'shift_id' => $shift->id]);
});

function scanne(string $code): TestResponse
{
    return test()->actingAs(test()->admin)->getJson(route('admin.badges.scan', ['code' => $code]));
}

function codeDuBadge(): string
{
    return app(BadgePrinter::class)->verificationUrl(test()->volunteer, test()->edition);
}

it('refuse le scanner a un visiteur et a un benevole', function () {
    $this->getJson(route('admin.badges.scan', ['code' => codeDuBadge()]))->assertUnauthorized();

    $this->actingAs($this->volunteer)
        ->getJson(route('admin.badges.scan', ['code' => codeDuBadge()]))
        ->assertForbidden();
});

it('valide le QR code d un benevole de l edition, avec son planning', function () {
    scanne(codeDuBadge())
        ->assertOk()
        ->assertJsonPath('status', 'valid')
        ->assertJsonPath('title', 'Badge valable')
        ->assertJsonPath('volunteer.name', 'Camille Dorel')
        ->assertJsonPath('volunteer.identifier', sprintf('SDD27-%04d', $this->volunteer->id))
        ->assertJsonPath('volunteer.edition', 'Salon de la Danse 2027')
        ->assertJsonPath('volunteer.url', route('admin.volunteers.show', $this->volunteer))
        ->assertJsonPath('volunteer.shifts.0.mission', 'Accueil exposants')
        ->assertJsonPath('volunteer.shifts.0.time', '10:00 - 12:00');
});

it('ne livre jamais les coordonnees du benevole', function () {
    $content = scanne(codeDuBadge())->assertOk()->getContent();

    expect($content)->not->toContain('camille.dorel@example.test')
        ->and($content)->not->toContain('0611223344');
});

it('ne reconnait pas un QR code retouche ou etranger au Salon', function (string $code) {
    scanne($code)
        ->assertOk()
        ->assertJsonPath('status', 'unrecognized')
        ->assertJsonPath('title', 'Badge non reconnu')
        ->assertJsonPath('volunteer', null);
})->with([
    'autre benevole, meme signature' => fn () => str_replace(
        '/'.test()->volunteer->id.'?',
        '/'.benevole(test()->edition)->id.'?',
        codeDuBadge(),
    ),
    'sans signature' => fn () => route('badges.verify', ['edition' => test()->edition->id, 'volunteer' => test()->volunteer->id]),
    'adresse quelconque' => 'https://example.test/promo',
    'texte libre' => 'bonjour',
]);

it('refuse une adresse signee qui ne mene pas a un badge', function () {
    // Une signature valide ne suffit pas : elle doit viser la vérification
    // d'un badge, pas n'importe quelle autre adresse signée de l'application.
    $autre = URL::signedRoute('legal.notice');

    scanne($autre)->assertOk()->assertJsonPath('status', 'unrecognized');
});

it('dit qu un vrai badge ne vaut plus quand le compte a quitte l edition', function () {
    $code = codeDuBadge();
    $this->volunteer->forceFill(['role' => UserRole::Admin])->save();

    scanne($code)
        ->assertOk()
        ->assertJsonPath('status', 'inactive')
        ->assertJsonPath('title', 'Badge non valable')
        ->assertJsonPath('volunteer.name', 'Camille Dorel');
});

it('dit qu un vrai badge n a plus de titulaire quand le compte est supprime', function () {
    $code = codeDuBadge();
    $this->volunteer->delete();

    scanne($code)
        ->assertOk()
        ->assertJsonPath('status', 'no_holder')
        ->assertJsonPath('volunteer', null);
});

it('valide l identifiant saisi a la main', function () {
    scanne(sprintf('sdd27-%04d', $this->volunteer->id))
        ->assertOk()
        ->assertJsonPath('status', 'valid')
        ->assertJsonPath('volunteer.name', 'Camille Dorel');
});

it('refuse un identifiant saisi inconnu, d une autre annee ou d un administrateur', function () {
    scanne('SDD27-999999')->assertJsonPath('status', 'unrecognized');
    scanne(sprintf('SDD26-%04d', $this->volunteer->id))->assertJsonPath('status', 'inactive');
    scanne(sprintf('SDD27-%04d', $this->admin->id))->assertJsonPath('status', 'inactive');
});

it('refuse un identifiant saisi quand aucune edition n est ouverte', function () {
    Edition::query()->update(['is_active' => false]);

    scanne(sprintf('SDD27-%04d', $this->volunteer->id))->assertJsonPath('status', 'unrecognized');
});

it('exige un code', function () {
    scanne('')->assertUnprocessable()->assertJsonValidationErrors(['code' => 'Scannez un badge ou saisissez son identifiant.']);
});

it('ouvre le scanner depuis tous les ecrans du back-office', function () {
    foreach ([route('admin.dashboard'), route('admin.badges.index'), route('admin.volunteers.show', $this->volunteer)] as $url) {
        $this->actingAs($this->admin)
            ->get($url)
            ->assertOk()
            ->assertSee("\$dispatch('open-modal', 'badge-scanner')", escape: false)
            ->assertSee('badgeScanner(', escape: false);
    }
});
