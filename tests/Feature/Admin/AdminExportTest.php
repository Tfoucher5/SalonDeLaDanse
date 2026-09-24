<?php

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * Le contenu des exports, pas seulement leur existence.
 *
 * Un fichier qui se télécharge mais dont il manque une ligne est pire qu'un
 * export absent : chaque cas vérifie donc ce qui est réellement écrit dedans,
 * et ce qui n'a pas à y être.
 */
beforeEach(function () {
    $this->edition = salon(['name' => 'Salon de la Danse 2027']);
    $this->admin = administrateur($this->edition);

    $this->accueil = creneau($this->edition, position: 1, date: '2027-05-14', capacity: 4);
    $this->accueil->mission->update(['name' => 'Accueil exposants']);

    $this->billetterie = creneau($this->edition, position: 3, date: '2027-05-15', capacity: 2, restricted: true);
    $this->billetterie->mission->update(['name' => 'Billetterie']);

    // Camille : Accueil le vendredi, planning validé, choix personnel.
    $this->camille = User::factory()->forEdition($this->edition)->validatedPlanning()->create([
        'first_name' => 'Camille', 'last_name' => 'Dorel',
        'email' => 'camille.dorel@example.test', 'phone' => '0611223344',
    ]);
    Assignment::factory()->create(['user_id' => $this->camille->id, 'shift_id' => $this->accueil->id]);

    // Naim : Billetterie le samedi, posé par l'équipe, planning en attente.
    $this->naim = benevole($this->edition);
    $this->naim->update([
        'first_name' => 'Naim', 'last_name' => 'Belkacem',
        'email' => 'naim.belkacem@example.test', 'phone' => '0655667788',
    ]);
    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $this->naim->id, 'shift_id' => $this->billetterie->id,
    ]);
});

function telecharge(string $dataset, string $format, array $criteria = []): TestResponse
{
    return test()->actingAs(test()->admin)->get(route('admin.exports.download', [
        'dataset' => $dataset,
        'format' => $format,
        ...$criteria,
    ]));
}

/**
 * Le CSV tel qu'Excel le lira, marque d'ordre des octets comprise.
 */
function csv(string $dataset, array $criteria = []): string
{
    return telecharge($dataset, 'csv', $criteria)->assertOk()->streamedContent();
}

/**
 * L'onglet du classeur, extrait de l'archive. C'est la preuve qu'il s'agit
 * bien d'un `.xlsx` et pas d'un fichier renommé.
 */
function onglet(string $dataset, array $criteria = []): string
{
    $path = telecharge($dataset, 'xlsx', $criteria)->assertOk()->baseResponse->getFile()->getPathname();

    $archive = new ZipArchive;

    expect($archive->open($path))->toBeTrue();

    $sheet = $archive->getFromName('xl/worksheets/sheet1.xml');
    $archive->close();

    expect($sheet)->toBeString();

    return $sheet;
}

it('sort le planning general en CSV, lisible par Excel en francais', function () {
    $content = csv('planning');

    // `fputcsv` entoure de guillemets tout champ qui contient un espace : les
    // lignes attendues sont celles qu'Excel lira, pas celles qu'on imagine.
    expect($content)->toStartWith("\u{FEFF}")
        ->and($content)->toContain('Date;Jour;Début;Fin;Mission;Accès;Nom;Prénom;E-mail;Téléphone;Attribution;"Statut du planning"')
        ->and($content)->toContain('2027-05-14;Vendredi;08:30;10:00;"Accueil exposants";Ouverte;Dorel;Camille;camille.dorel@example.test;0611223344;Bénévole;Validé')
        // Naim est « Verrouillé » et non « Non validé » : l'attribution de
        // l'équipe organisatrice ferme son planning, l'export le dit.
        ->and($content)->toContain('2027-05-15;Samedi;12:00;14:00;Billetterie;Restreinte;Belkacem;Naim;naim.belkacem@example.test;0655667788;"Équipe organisatrice";Verrouillé');
});

it('sort le meme planning dans un vrai classeur Excel', function () {
    $sheet = onglet('planning');

    expect($sheet)->toContain('Accueil exposants')
        ->toContain('Camille')
        ->toContain('Billetterie')
        ->toContain('Belkacem')
        // L'en-tête est figé et filtrable : c'est ce qui rend la feuille utilisable.
        ->toContain('state="frozen"')
        ->toContain('<autoFilter');
});

it('produit une archive Excel complete et bien formee', function () {
    $path = telecharge('planning', 'xlsx')->assertOk()->baseResponse->getFile()->getPathname();

    $archive = new ZipArchive;
    $archive->open($path);

    // Le classeur est écrit à la main : ce test est le garde-fou. Une pièce
    // manquante ou un XML mal formé, et Excel refuse le fichier en bloc sans
    // rien dire de plus qu'« il y a un problème avec le contenu ».
    $parts = [
        '[Content_Types].xml',
        '_rels/.rels',
        'xl/workbook.xml',
        'xl/_rels/workbook.xml.rels',
        'xl/styles.xml',
        'xl/worksheets/sheet1.xml',
    ];

    foreach ($parts as $part) {
        $xml = $archive->getFromName($part);

        expect($xml)->toBeString("La pièce {$part} manque à l'archive.");

        $document = new DOMDocument;

        expect($document->loadXML($xml))->toBeTrue("La pièce {$part} n'est pas un XML bien formé.");
    }

    $archive->close();
});

it('echappe ce qu un nom peut contenir de plus hostile au XML', function () {
    // Un « & » ou un chevron dans un nom casserait l'archive entière ; un
    // caractère de contrôle collé depuis un tableur aussi.
    $this->camille->update(['last_name' => "D'Arc & <Fils>\x07"]);

    $sheet = onglet('planning');

    expect($sheet)->toContain('&amp;')
        ->toContain('&lt;Fils&gt;')
        ->not->toContain("\x07");

    $document = new DOMDocument;

    expect($document->loadXML($sheet))->toBeTrue();
});

it('nomme le fichier telecharge d apres la feuille et l edition', function () {
    telecharge('contacts', 'xlsx')
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload();

    expect(telecharge('contacts', 'xlsx')->headers->get('content-disposition'))
        ->toContain('fiches-contact-salon-de-la-danse-2027-');
});

it('comprend les missions restreintes, que le benevole ne voit jamais', function () {
    expect(csv('planning'))->toContain('Billetterie')
        ->and(csv('missions'))->toContain('Billetterie;Restreinte');

    $this->actingAs($this->naim)->get(route('planning.index'))->assertOk()->assertDontSee('Billetterie');
});

it('sort la liste par mission avec sa jauge et ses creneaux vides', function () {
    $vide = creneau($this->edition, position: 5, date: '2027-05-16', capacity: 3);
    $vide->mission->update(['name' => 'Zone logistique']);

    $content = csv('missions');

    expect($content)->toContain('Mission;Accès;Date;Jour;Début;Fin;Places;Prises;Restantes;Bénévoles')
        ->and($content)->toContain('"Accueil exposants";Ouverte;2027-05-14;Vendredi;08:30;10:00;4;1;3;"Camille Dorel"')
        ->and($content)->toContain('Billetterie;Restreinte;2027-05-15;Samedi;12:00;14:00;2;1;1;"Naim Belkacem"')
        // Le créneau que personne n'occupe est la raison d'être de cette feuille.
        ->and($content)->toContain('"Zone logistique";Ouverte;2027-05-16;Dimanche;16:00;18:00;3;0;3;');
});

it('sort les fiches contact, une ligne par benevole', function () {
    $content = csv('contacts');

    expect($content)->toContain('Nom;Prénom;E-mail;Téléphone;"Date de naissance";Créneaux;"Statut du planning"')
        ->and($content)->toContain('Belkacem;Naim;naim.belkacem@example.test;0655667788;')
        ->and($content)->toContain('Dorel;Camille;camille.dorel@example.test;0611223344;')
        // Une ligne par bénévole, pas une par créneau.
        ->and(substr_count($content, 'camille.dorel@example.test'))->toBe(1);
});

it('filtre l export exactement comme la liste a l ecran', function () {
    expect(csv('planning', ['name' => 'Dorel']))
        ->toContain('Camille')->not->toContain('Belkacem');

    expect(csv('planning', ['mission' => $this->billetterie->mission_id]))
        ->toContain('Belkacem')->not->toContain('Dorel');

    expect(csv('planning', ['day' => '2027-05-14']))
        ->toContain('Dorel')->not->toContain('Belkacem');

    expect(csv('contacts', ['status' => 'validated']))
        ->toContain('Dorel')->not->toContain('Belkacem');

    expect(csv('contacts', ['status' => 'pending']))
        ->toContain('Belkacem')->not->toContain('Dorel');
});

it('ignore le filtre par nom sur la liste par mission', function () {
    // Cette feuille est centrée sur le créneau : filtrer par bénévole y ferait
    // disparaître les créneaux vides, c'est-à-dire ce qu'elle sert à montrer.
    $content = csv('missions', ['name' => 'Dorel']);

    expect($content)->toContain('Accueil exposants')->toContain('Billetterie');

    // Le filtre par mission, lui, s'applique bien.
    expect(csv('missions', ['mission' => $this->accueil->mission_id]))
        ->toContain('Accueil exposants')->not->toContain('Billetterie');
});

it('ne sort jamais une edition voisine', function () {
    $autre = salon();
    $etranger = benevole($autre);
    $etranger->update(['first_name' => 'Elio', 'last_name' => 'Vasseur']);

    $ailleurs = creneau($autre, position: 1);
    $ailleurs->mission->update(['name' => 'Mission voisine']);
    Assignment::factory()->create(['user_id' => $etranger->id, 'shift_id' => $ailleurs->id]);

    expect(csv('planning'))->not->toContain('Vasseur')
        ->and(csv('contacts'))->not->toContain('Vasseur')
        ->and(csv('missions'))->not->toContain('Mission voisine');
});

it('rend une feuille en-tetes seuls quand aucune ligne ne correspond', function () {
    $content = csv('contacts', ['name' => 'Personne']);

    expect($content)->toContain('Nom;Prénom')
        ->not->toContain('Dorel')
        ->not->toContain('Belkacem');

    // Le classeur reste un classeur valide, même vide.
    expect(onglet('contacts', ['name' => 'Personne']))->toContain('<sheetData>');
});

it('refuse une feuille ou un format inconnus', function () {
    telecharge('plannings', 'csv')->assertInvalid(['dataset']);
    telecharge('planning', 'pdf')->assertInvalid(['format']);
});

it('ferme les exports a tout le monde sauf a l administrateur', function () {
    $this->get(route('admin.exports.download', ['dataset' => 'contacts', 'format' => 'csv']))->assertRedirect('/login');

    $this->actingAs($this->camille)->get(route('admin.exports.download', ['dataset' => 'contacts', 'format' => 'csv']))
        ->assertForbidden();
});

it('ne sort du planning general que les creneaux du filtre, pas tout le planning des benevoles retenus', function () {
    // Camille prend aussi la Billetterie le samedi : filtrer sur l'Accueil ou
    // sur le vendredi la retient, mais ne doit pas ramener ce créneau-là.
    Assignment::factory()->create(['user_id' => $this->camille->id, 'shift_id' => $this->billetterie->id]);

    expect(csv('planning', ['mission' => $this->accueil->mission_id]))
        ->toContain('Accueil exposants')->not->toContain('Billetterie');

    expect(csv('planning', ['day' => '2027-05-14']))
        ->toContain('2027-05-14')->not->toContain('2027-05-15');
});

it('exporte la liste des benevoles depuis sa fenetre, criteres repris', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.volunteers.index', ['name' => 'Dorel', 'status' => 'validated']))
        ->assertOk()
        ->assertSee('Exporter cette vue')
        ->assertSee('action="'.route('admin.exports.download').'"', escape: false)
        ->assertSee('<input type="hidden" name="name" value="Dorel">', escape: false)
        ->assertSee('<input type="hidden" name="status" value="validated">', escape: false)
        ->assertSee('Nom : « Dorel »')
        ->assertSeeInOrder(['Fiches contact', 'Planning général'])
        ->assertDontSee('Liste par mission');
});

it('exporte le planning du jour affiche depuis sa fenetre', function () {
    // Sans jour dans l'URL, l'écran montre le premier : l'export aussi.
    $this->actingAs($this->admin)
        ->get(route('admin.planning', ['mission' => $this->accueil->mission_id]))
        ->assertOk()
        ->assertSee('<input type="hidden" name="mission" value="'.$this->accueil->mission_id.'">', escape: false)
        ->assertSee('Mission : Accueil exposants')
        ->assertSee('name="day" value="2027-05-14" checked', escape: false)
        ->assertSee('Tous les jours')
        ->assertSeeInOrder(['Liste par mission', 'Planning général']);
});
