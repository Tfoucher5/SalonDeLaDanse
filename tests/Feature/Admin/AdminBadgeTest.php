<?php

use App\Models\Assignment;
use App\Models\Edition;
use App\Services\Badges\Badge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * Les badges du back-office : qui peut les imprimer, et qui s'y trouve.
 *
 * Le texte d'un PDF dompdf n'est pas lisible en clair (polices embarquées,
 * flux compressés) : le contenu se vérifie sur les données remises à la vue
 * du PDF, que `badgesImprimes()` capture au moment du rendu.
 */
beforeEach(function () {
    Storage::fake('public');

    $this->edition = salon(['name' => 'Salon de la Danse 2027']);
    $this->admin = administrateur($this->edition);

    $this->accueil = creneau($this->edition, position: 1);

    $this->camille = benevole($this->edition);
    $this->camille->update(['first_name' => 'Camille', 'last_name' => 'Dorel']);
    Assignment::factory()->create(['user_id' => $this->camille->id, 'shift_id' => $this->accueil->id]);

    $this->naim = benevole($this->edition);
    $this->naim->update(['first_name' => 'Naim', 'last_name' => 'Belkacem']);

    // Ni l'un ni l'autre ne doit jamais recevoir de badge de cette édition.
    $this->autreEdition = benevole(Edition::factory()->create(['is_active' => false]));
});

/**
 * Les badges passés à la vue du PDF, capturés pendant la requête.
 *
 * @return Collection<int, Badge>
 */
function badgesImprimes(callable $request): Collection
{
    $captured = collect();

    View::composer('admin.badges.pdf', function ($view) use ($captured): void {
        $view->getData()['sheets']->flatten(1)->each(fn (Badge $badge) => $captured->push($badge));
    });

    $request();

    return $captured;
}

function genere(array $payload)
{
    return test()->actingAs(test()->admin)->post(route('admin.badges.download'), $payload);
}

it('redirige un visiteur anonyme vers la connexion', function () {
    $this->get(route('admin.badges.index'))->assertRedirect('/login');
    $this->get(route('admin.volunteers.badge', $this->camille))->assertRedirect('/login');
    $this->post(route('admin.badges.download'), ['mode' => 'filters'])->assertRedirect('/login');
});

it('refuse les badges a un benevole', function () {
    $this->actingAs($this->naim)->get(route('admin.badges.index'))->assertForbidden();
    $this->actingAs($this->naim)->get(route('admin.volunteers.badge', $this->naim))->assertForbidden();
    $this->actingAs($this->naim)->post(route('admin.badges.download'), ['mode' => 'filters'])->assertForbidden();
});

it('telecharge le badge d un benevole seul, nomme par son identifiant', function () {
    $identifiant = sprintf('SDD27-%04d', $this->camille->id);

    $badges = badgesImprimes(fn () => $this->actingAs($this->admin)
        ->get(route('admin.volunteers.badge', $this->camille))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('badge-'.$identifiant.'.pdf'));

    expect($badges)->toHaveCount(1)
        ->and($badges->first()->firstName)->toBe('Camille')
        ->and($badges->first()->lastName)->toBe('Dorel')
        ->and($badges->first()->identifier)->toBe($identifiant)
        ->and($badges->first()->editionName)->toBe('Salon de la Danse 2027')
        ->and($badges->first()->qrCode)->toStartWith('data:image/svg+xml;base64,');
});

it('produit un vrai document PDF', function () {
    $content = $this->actingAs($this->admin)
        ->get(route('admin.volunteers.badge', $this->camille))
        ->assertOk()
        ->getContent();

    expect($content)->toStartWith('%PDF-');
});

it('encode dans le QR code l adresse signee de la verification', function () {
    $badge = badgesImprimes(fn () => $this->actingAs($this->admin)
        ->get(route('admin.volunteers.badge', $this->camille)))->first();

    auth()->logout();

    $this->get($badge->verificationUrl)->assertOk()->assertSee('Camille Dorel');
});

it('ne fait pas de badge a un compte hors de l edition', function () {
    $this->actingAs($this->admin)->get(route('admin.volunteers.badge', $this->admin))->assertNotFound();
    $this->actingAs($this->admin)->get(route('admin.volunteers.badge', $this->autreEdition))->assertNotFound();
});

it('propose le badge depuis la fiche du benevole', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.volunteers.show', $this->camille))
        ->assertOk()
        ->assertSee(route('admin.volunteers.badge', $this->camille), escape: false)
        ->assertSee('Badge (PDF)');
});

it('imprime en mode filtres les seuls benevoles des criteres', function () {
    $badges = badgesImprimes(fn () => genere(['mode' => 'filters', 'mission' => $this->accueil->mission_id])
        ->assertOk());

    expect($badges->pluck('volunteerId')->all())->toBe([$this->camille->id]);
});

it('imprime en mode filtres toute l edition, sans administrateur ni autre edition', function () {
    $badges = badgesImprimes(fn () => genere(['mode' => 'filters'])
        ->assertOk()
        ->assertDownload('badges-SDD27.pdf'));

    // Ordre alphabétique des noms : Belkacem, puis Dorel.
    expect($badges->pluck('volunteerId')->all())->toBe([$this->naim->id, $this->camille->id]);
});

it('signale une recherche qui ne retient personne', function () {
    genere(['mode' => 'filters', 'name' => 'Personne'])
        ->assertRedirect()
        ->assertSessionHasErrors(['mode' => 'Aucun bénévole ne correspond à ces critères : il n\'y a aucun badge à imprimer.']);
});

it('imprime en mode selection les seuls benevoles coches, une fois chacun', function () {
    $badges = badgesImprimes(fn () => genere([
        'mode' => 'selection',
        'volunteers' => [$this->naim->id, $this->naim->id],
        // Les critères accompagnent toujours le formulaire : ils ne comptent pas ici.
        'mission' => $this->accueil->mission_id,
    ])->assertOk()->assertDownload('badge-'.sprintf('SDD27-%04d', $this->naim->id).'.pdf'));

    expect($badges->pluck('volunteerId')->all())->toBe([$this->naim->id]);
});

it('refuse en mode selection un compte qui n est pas benevole de l edition', function (string $intrus) {
    $id = match ($intrus) {
        'inconnu' => 999999,
        'autre edition' => $this->autreEdition->id,
        'administrateur' => $this->admin->id,
    };

    genere(['mode' => 'selection', 'volunteers' => [$this->camille->id, $id]])
        ->assertRedirect()
        ->assertSessionHasErrors(['volunteers' => 'Un des bénévoles cochés n\'appartient pas à cette édition.']);
})->with(['inconnu', 'autre edition', 'administrateur']);

it('exige au moins une case cochee en mode selection', function () {
    genere(['mode' => 'selection'])
        ->assertSessionHasErrors(['volunteers' => 'Cochez au moins un bénévole.']);
});

it('exige un mode de generation connu', function () {
    genere(['mode' => 'tout'])->assertSessionHasErrors(['mode' => 'Choisissez les bénévoles à imprimer.']);
});

it('imprime le badge d un benevole sans photo avec ses initiales', function () {
    $badge = badgesImprimes(fn () => $this->actingAs($this->admin)
        ->get(route('admin.volunteers.badge', $this->naim))
        ->assertOk())->first();

    expect($badge->hasPhoto())->toBeFalse()
        ->and($badge->initials)->toBe('NB');
});

it('recadre la photo du benevole en JPEG pour le badge', function () {
    $this->camille->forceFill(['photo_path' => fakePhoto()->store(config('salon.photo.directory'), 'public')])->save();

    $badge = badgesImprimes(fn () => $this->actingAs($this->admin)
        ->get(route('admin.volunteers.badge', $this->camille))
        ->assertOk())->first();

    expect($badge->photo)->toStartWith('data:image/jpeg;base64,');
})->skip(! extension_loaded('gd'), 'Le recadrage de la photo demande l\'extension GD.');

it('liste avant impression les benevoles sans photo', function () {
    $this->camille->forceFill(['photo_path' => fakePhoto()->store(config('salon.photo.directory'), 'public')])->save();

    // Un chemin en base sans fichier derrière : le badge n'aura pas de photo.
    $this->naim->forceFill(['photo_path' => 'volunteers/photos/disparue.png'])->save();

    $this->actingAs($this->admin)
        ->get(route('admin.badges.index'))
        ->assertOk()
        ->assertSee('1 bénévole sans photo')
        ->assertSeeInOrder(['sans photo', 'Naim Belkacem', 'Camille Dorel']);
});

it('affiche la page des badges avec l identifiant de chacun', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.badges.index', ['mission' => $this->accueil->mission_id]))
        ->assertOk()
        ->assertSee('Camille Dorel')
        ->assertSee(sprintf('SDD27-%04d', $this->camille->id))
        ->assertSeeInOrder(['1 bénévole', 'pour cette recherche'])
        ->assertSee('Imprimer le badge (PDF)');
});

it('reste consultable sans aucune edition en base', function () {
    Edition::query()->update(['is_active' => false]);

    $this->actingAs($this->admin)->get(route('admin.badges.index'))->assertOk()->assertSee('Aucune édition active');
    genere(['mode' => 'filters'])->assertNotFound();
});
