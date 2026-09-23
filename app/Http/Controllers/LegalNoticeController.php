<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Mentions legales et donnees personnelles.
 *
 * Page publique : elle doit rester lisible avant toute connexion, depuis
 * l'accueil comme depuis l'espace benevole.
 */
class LegalNoticeController extends Controller
{
    public function __invoke(): View
    {
        return view('legal.notice', [
            'publisher' => config('salon.legal.publisher'),
            'host' => array_filter(config('salon.legal.host')),
            'retentionMonths' => config('salon.legal.data_retention_months'),
        ]);
    }
}
