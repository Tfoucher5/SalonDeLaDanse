<?php

namespace App\Services\Export;

use Symfony\Component\HttpFoundation\Response;

/**
 * Transforme une feuille en fichier téléchargeable.
 *
 * Une implémentation par format. Le contrôleur choisit l'écrivain, il
 * n'assemble jamais lui-même le moindre octet.
 */
interface SpreadsheetWriter
{
    public function download(Spreadsheet $sheet, string $filename): Response;
}
