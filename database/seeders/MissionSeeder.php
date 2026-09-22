<?php

namespace Database\Seeders;

use App\Models\Edition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MissionSeeder extends Seeder
{
    /**
     * Les 9 missions ouvertes a la reservation, puis les 2 sous restriction.
     *
     * Une mission restreinte n apparait jamais dans la grille du benevole : elle
     * est attribuee manuellement depuis le back-office.
     *
     * @var array<int, array{string, bool}>
     */
    private const MISSIONS = [
        ['Accueil exposants', true],
        ['Vestiaires', true],
        ['Point Info', true],
        ['Masterclass / Conférences', true],
        ['Loges danseurs', true],
        ['Logistique (Niveau 0 + -2)', true],
        ['Scène principale', true],
        ['Stand JayDance', true],
        ['Village Danses du Monde', true],
        ['Billetterie', false],
        ['Caisse', false],
    ];

    public function run(): void
    {
        $edition = Edition::current();

        if ($edition === null) {
            return;
        }

        foreach (self::MISSIONS as $position => [$name, $isPublic]) {
            $edition->missions()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_public' => $isPublic,
                    'position' => $position + 1,
                ],
            );
        }
    }
}
