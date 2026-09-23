<?php

use App\Models\Assignment;
use App\Models\Mission;
use App\Models\Shift;

it('redirige un visiteur anonyme vers la connexion', function () {
    $this->get(route('planning.summary'))->assertRedirect('/login');
});

it('annonce un planning vide plutot qu une page blanche', function () {
    $edition = salon();

    $this->actingAs(benevole($edition))
        ->get(route('planning.summary'))
        ->assertOk()
        ->assertSee('Votre planning est encore vide');
});

it('restitue les missions, les horaires et les consignes', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    $mission = Mission::factory()->create([
        'edition_id' => $edition->id,
        'name' => 'Loges danseurs',
        'instructions' => 'Badge obligatoire, accès par la porte B.',
    ]);

    $shift = Shift::factory()->create([
        'edition_id' => $edition->id,
        'mission_id' => $mission->id,
        'time_slot_id' => $edition->timeSlots()->where('position', 2)->value('id'),
        'date' => '2027-05-15',
    ]);

    Assignment::factory()->create(['user_id' => $volunteer->id, 'shift_id' => $shift->id]);

    $this->actingAs($volunteer)
        ->get(route('planning.summary'))
        ->assertOk()
        ->assertSee('Loges danseurs')
        ->assertSee('10:00 - 12:00')
        ->assertSee('Badge obligatoire, accès par la porte B.')
        ->assertSee('Samedi 15 mai 2027');
});

it('ordonne les creneaux par jour puis par tranche horaire', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    // Cree dans le desordre : la fiche doit les remettre en ordre de journee.
    foreach ([['2027-05-15', 1], ['2027-05-14', 4], ['2027-05-14', 2]] as [$date, $position]) {
        Assignment::factory()->create([
            'user_id' => $volunteer->id,
            'shift_id' => creneau($edition, position: $position, date: $date)->id,
        ]);
    }

    $content = $this->actingAs($volunteer)->get(route('planning.summary'))->assertOk()->getContent();

    expect(mb_strpos($content, '10:00 - 12:00'))
        ->toBeLessThan(mb_strpos($content, '14:00 - 16:00'))
        ->and(mb_strpos($content, '14:00 - 16:00'))
        ->toBeLessThan(mb_strpos($content, '08:30 - 10:00'));
});

it('ne laisse fuiter aucun autre benevole inscrit sur le meme creneau', function () {
    $edition = salon();
    $volunteer = benevole($edition);
    $shift = creneau($edition, position: 1);

    $other = benevole($edition);
    $other->update(['first_name' => 'Solene', 'last_name' => 'Marchand']);

    Assignment::factory()->create(['user_id' => $volunteer->id, 'shift_id' => $shift->id]);
    Assignment::factory()->create(['user_id' => $other->id, 'shift_id' => $shift->id]);

    $this->actingAs($volunteer)
        ->get(route('planning.summary'))
        ->assertOk()
        ->assertDontSee('Solene')
        ->assertDontSee('Marchand')
        ->assertDontSee($other->email);
});

it('signale un creneau attribue par l equipe organisatrice', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    Assignment::factory()->forcedByAdmin()->create([
        'user_id' => $volunteer->id,
        'shift_id' => creneau($edition, position: 1, restricted: true)->id,
    ]);

    $this->actingAs($volunteer)
        ->get(route('planning.summary'))
        ->assertOk()
        ->assertSee('Attribué par l\'équipe organisatrice', escape: false);
});

it('se prepare pour le papier en masquant la navigation', function () {
    $edition = salon();
    $volunteer = benevole($edition);

    Assignment::factory()->create([
        'user_id' => $volunteer->id,
        'shift_id' => creneau($edition, position: 1)->id,
    ]);

    $this->actingAs($volunteer)
        ->get(route('planning.summary'))
        ->assertOk()
        ->assertSee('print-hidden')
        ->assertSee('window.print()', escape: false);

    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain('@media print')
        ->toContain('break-inside: avoid');
});
