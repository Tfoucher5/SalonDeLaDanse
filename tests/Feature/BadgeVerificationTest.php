<?php

use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\Edition;
use App\Services\Badges\BadgePrinter;

/**
 * La page ouverte par le QR code d'un badge. Publique, donc visitée ici sans
 * compte : elle doit reconnaître un vrai badge, refuser une adresse retouchée,
 * dire quand le badge ne vaut plus, et ne rien livrer d'autre que l'identité.
 */
beforeEach(function () {
    $this->edition = salon(['name' => 'Salon de la Danse 2027']);

    $this->volunteer = benevole($this->edition);
    $this->volunteer->update([
        'first_name' => 'Camille',
        'last_name' => 'Dorel',
        'email' => 'camille.dorel@example.test',
        'phone' => '0611223344',
    ]);
});

function adresseDuBadge(): string
{
    return app(BadgePrinter::class)->verificationUrl(test()->volunteer, test()->edition);
}

it('reconnait un badge authentique sans rien livrer des coordonnees', function () {
    $this->get(adresseDuBadge())
        ->assertOk()
        ->assertSee('Camille Dorel')
        ->assertSee('Bénévole')
        ->assertSee('Salon de la Danse 2027')
        ->assertSee(sprintf('SDD27-%04d', $this->volunteer->id))
        ->assertSee('Badge valable')
        ->assertDontSee('camille.dorel@example.test')
        ->assertDontSee('0611223344')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('refuse une adresse dont on a change le benevole', function () {
    $autre = benevole($this->edition);
    $retouchee = str_replace(
        '/badges/'.$this->edition->id.'/'.$this->volunteer->id.'?',
        '/badges/'.$this->edition->id.'/'.$autre->id.'?',
        adresseDuBadge(),
    );

    expect($retouchee)->not->toBe(adresseDuBadge());

    $this->get($retouchee)
        ->assertForbidden()
        ->assertSee('Badge non reconnu')
        ->assertDontSee($autre->last_name);
});

it('refuse une adresse sans signature', function () {
    $this->get(route('badges.verify', ['edition' => $this->edition->id, 'volunteer' => $this->volunteer->id]))
        ->assertForbidden()
        ->assertSee('Badge non reconnu');
});

it('dit que le badge ne vaut plus quand le compte a quitte l edition', function (string $depart) {
    $url = adresseDuBadge();

    match ($depart) {
        'devenu administrateur' => $this->volunteer->forceFill(['role' => UserRole::Admin])->save(),
        'rattache a une autre edition' => $this->volunteer->forceFill(['edition_id' => Edition::factory()->create(['is_active' => false])->id])->save(),
        'edition close' => $this->edition->update(['is_active' => false]),
    };

    $this->get($url)
        ->assertOk()
        ->assertSee('Camille Dorel')
        ->assertSee('Badge non valable')
        ->assertSee('n\'est plus bénévole de cette édition', escape: false)
        ->assertDontSee('Badge valable', escape: false);
})->with(['devenu administrateur', 'rattache a une autre edition', 'edition close']);

it('dit que le badge n a plus de titulaire quand le compte est supprime', function () {
    $url = adresseDuBadge();

    $this->volunteer->delete();

    $this->get($url)
        ->assertOk()
        ->assertSee('Badge non valable')
        ->assertSee('Ce badge n\'a plus de titulaire', escape: false);
});

it('echappe le nom affiche', function () {
    $this->volunteer->update(['last_name' => '<script>alert(1)</script>']);

    $this->get(adresseDuBadge())
        ->assertOk()
        ->assertSee('&lt;script&gt;', escape: false)
        ->assertDontSee('<script>alert(1)</script>', escape: false);
});

it('montre le planning du benevole a un administrateur connecte', function () {
    $shift = creneau($this->edition, position: 2);
    $shift->mission->update(['name' => 'Accueil exposants']);
    Assignment::factory()->create(['user_id' => $this->volunteer->id, 'shift_id' => $shift->id]);

    $this->actingAs(administrateur($this->edition))
        ->get(adresseDuBadge())
        ->assertOk()
        ->assertSee('Contrôle équipe')
        ->assertSee('Accueil exposants')
        ->assertSee('10:00 - 12:00')
        ->assertSee(route('admin.volunteers.show', $this->volunteer), escape: false)
        ->assertDontSee('camille.dorel@example.test')
        ->assertDontSee('0611223344');
});

it('ne montre jamais le planning a un visiteur ni a un autre benevole', function () {
    $shift = creneau($this->edition, position: 2);
    $shift->mission->update(['name' => 'Accueil exposants']);
    Assignment::factory()->create(['user_id' => $this->volunteer->id, 'shift_id' => $shift->id]);

    $this->get(adresseDuBadge())
        ->assertOk()
        ->assertDontSee('Contrôle équipe')
        ->assertDontSee('Accueil exposants')
        ->assertSee('Connectez-vous');

    // La confidentialité protège les bénévoles entre eux : un collègue qui
    // scanne le badge n'apprend rien de plus qu'un visiteur.
    $this->actingAs(benevole($this->edition))
        ->get(adresseDuBadge())
        ->assertOk()
        ->assertDontSee('Contrôle équipe')
        ->assertDontSee('Accueil exposants');
});

it('ramene sur le badge l administrateur qui se connecte apres le scan', function () {
    $admin = administrateur($this->edition);
    $url = adresseDuBadge();

    $this->get($url)->assertOk();

    $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect($url);
});
