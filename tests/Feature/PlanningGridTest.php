<?php

use App\Models\Assignment;
use App\Models\Edition;
use App\Models\Mission;
use App\Models\Shift;
use App\Models\TimeSlot;
use App\Models\User;

/**
 * Le jeu de donnees minimal de la grille : une edition, une tranche horaire,
 * une mission publique, et le creneau qui les relie le premier jour du Salon.
 *
 * @return array{edition: Edition, slot: TimeSlot, mission: Mission, shift: Shift}
 */
function grid(int $capacity = 4): array
{
    $edition = Edition::factory()->create();

    $slot = TimeSlot::factory()->atPosition(1, '08:30', '10:00')->create([
        'edition_id' => $edition->id,
    ]);

    $mission = Mission::factory()->create([
        'edition_id' => $edition->id,
        'name' => 'Accueil exposants',
        'position' => 1,
    ]);

    $shift = Shift::factory()->withCapacity($capacity)->create([
        'edition_id' => $edition->id,
        'mission_id' => $mission->id,
        'time_slot_id' => $slot->id,
        'date' => '2027-05-14',
    ]);

    return ['edition' => $edition, 'slot' => $slot, 'mission' => $mission, 'shift' => $shift];
}

it('redirige un visiteur anonyme vers la connexion', function () {
    $this->get('/planning')->assertRedirect('/login');
});

it('affiche les missions du jour avec leur tranche horaire et leur jauge', function () {
    ['edition' => $edition] = grid(capacity: 4);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning')
        ->assertSee('Accueil exposants')
        ->assertSee('08:30 - 10:00')
        ->assertSee('4 places restantes');
});

it('ecarte les missions sous restriction de la grille', function () {
    ['edition' => $edition, 'slot' => $slot] = grid();

    $billetterie = Mission::factory()->restricted()->create([
        'edition_id' => $edition->id,
        'name' => 'Billetterie',
        'position' => 10,
    ]);

    Shift::factory()->create([
        'edition_id' => $edition->id,
        'mission_id' => $billetterie->id,
        'time_slot_id' => $slot->id,
        'date' => '2027-05-14',
    ]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning')
        ->assertSee('Accueil exposants')
        ->assertDontSee('Billetterie');
});

it('ne divulgue le nom d aucun autre benevole inscrit sur un creneau', function () {
    ['edition' => $edition, 'shift' => $shift] = grid(capacity: 4);

    $autre = User::factory()->forEdition($edition)->create(['last_name' => 'Duchesneau']);
    Assignment::factory()->create(['user_id' => $autre->id, 'shift_id' => $shift->id]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning')
        ->assertDontSee('Duchesneau')
        ->assertDontSee($autre->email)
        ->assertSee('3 places restantes');
});

it('affiche un creneau complet en gris, jamais comme une erreur', function () {
    ['edition' => $edition, 'shift' => $shift] = grid(capacity: 1);

    Assignment::factory()->create(['shift_id' => $shift->id]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning')
        ->assertSee('Complet')
        ->assertSee('Toutes les places de ce créneau sont prises.')
        ->assertSee('text-gauge-full', escape: false)
        ->assertDontSee('text-danger', escape: false);
});

it('bascule la jauge en ambre quand il ne reste presque plus de place', function () {
    ['edition' => $edition, 'shift' => $shift] = grid(capacity: 4);

    Assignment::factory()->count(3)->create(['shift_id' => $shift->id]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning')
        ->assertSee('1 place restante')
        ->assertSee('text-gauge-tight', escape: false);
});

it('marque les creneaux deja retenus par le benevole', function () {
    ['edition' => $edition, 'shift' => $shift] = grid();

    $user = User::factory()->forEdition($edition)->create();
    Assignment::factory()->create(['user_id' => $user->id, 'shift_id' => $shift->id]);

    $this->actingAs($user)
        ->get('/planning')
        ->assertSee('Réservé')
        ->assertSee('1 créneau');
});

it('propose la reservation tant que le planning est modifiable', function () {
    ['edition' => $edition] = grid();

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning')
        ->assertSee('Réserver');
});

it('propose le retrait d un creneau deja retenu', function () {
    ['edition' => $edition, 'shift' => $shift] = grid();

    $user = User::factory()->forEdition($edition)->create();
    Assignment::factory()->create(['user_id' => $user->id, 'shift_id' => $shift->id]);

    $this->actingAs($user)
        ->get('/planning')
        ->assertSee('Retirer ce créneau');
});

it('ne propose aucune action quand les inscriptions sont fermees', function () {
    ['edition' => $edition] = grid();
    $edition->update(['is_locked' => true]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning')
        ->assertDontSee('Réserver')
        ->assertDontSee('Retirer ce créneau');
});

it('navigue d un jour a l autre du Salon', function () {
    ['edition' => $edition, 'slot' => $slot] = grid();

    $vestiaires = Mission::factory()->create([
        'edition_id' => $edition->id,
        'name' => 'Vestiaires',
        'position' => 2,
    ]);

    Shift::factory()->create([
        'edition_id' => $edition->id,
        'mission_id' => $vestiaires->id,
        'time_slot_id' => $slot->id,
        'date' => '2027-05-15',
    ]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning?day=2027-05-15')
        ->assertSee('Vestiaires')
        ->assertDontSee('Accueil exposants');
});

it('retombe sur le premier jour quand le jour demande n existe pas', function () {
    ['edition' => $edition] = grid();

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning?day=2030-01-01')
        ->assertOk()
        ->assertSee('Accueil exposants');
});

it('ignore les creneaux d une autre edition', function () {
    ['edition' => $edition] = grid();

    $autreEdition = Edition::factory()->create(['is_active' => false]);
    $autreSlot = TimeSlot::factory()->atPosition(1, '08:30', '10:00')->create([
        'edition_id' => $autreEdition->id,
    ]);
    $autreMission = Mission::factory()->create([
        'edition_id' => $autreEdition->id,
        'name' => 'Village Danses du Monde',
        'position' => 1,
    ]);

    Shift::factory()->create([
        'edition_id' => $autreEdition->id,
        'mission_id' => $autreMission->id,
        'time_slot_id' => $autreSlot->id,
        'date' => '2027-05-14',
    ]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning')
        ->assertSee('Accueil exposants')
        ->assertDontSee('Village Danses du Monde');
});

it('affiche le motif de blocage quand les inscriptions sont fermees', function () {
    ['edition' => $edition] = grid();
    $edition->update([
        'registration_opens_at' => now()->subMonth(),
        'registration_closes_at' => now()->subDay(),
    ]);

    $this->actingAs(User::factory()->forEdition($edition)->create())
        ->get('/planning')
        ->assertOk()
        ->assertSee('Les inscriptions au planning sont fermées.')
        ->assertSee('Accueil exposants');
});

it('affiche le motif de blocage quand le planning est deja valide', function () {
    ['edition' => $edition] = grid();

    $this->actingAs(User::factory()->forEdition($edition)->validatedPlanning()->create())
        ->get('/planning')
        ->assertSee('Votre planning est validé définitivement.');
});

it('annonce un planning verrouille par l equipe organisatrice', function () {
    ['edition' => $edition, 'shift' => $shift] = grid();

    $user = User::factory()->forEdition($edition)->create();
    Assignment::factory()->forcedByAdmin()->create(['user_id' => $user->id, 'shift_id' => $shift->id]);

    $this->actingAs($user)
        ->get('/planning')
        ->assertSee('Verrouillé')
        ->assertDontSee('Réserver')
        ->assertDontSee('Retirer ce créneau');
});

it('reste consultable sans aucune edition en base', function () {
    $this->actingAs(User::factory()->create(['edition_id' => null]))
        ->get('/planning')
        ->assertOk()
        ->assertSee("Aucune édition n'est ouverte pour le moment.");
});
