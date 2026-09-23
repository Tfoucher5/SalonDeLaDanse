<?php

namespace App\Http\Requests\Admin;

use App\Models\Mission;

/**
 * Création d'une mission.
 *
 * Aucun contrôle de jauge ici : une mission neuve n'a encore personne dessus.
 */
class StoreMissionRequest extends MissionRequest
{
    protected function mission(): ?Mission
    {
        return null;
    }
}
