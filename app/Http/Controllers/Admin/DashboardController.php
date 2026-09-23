<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Services\EditionOverview;
use Illuminate\Contracts\View\View;

/**
 * Vue d'ensemble du dispositif : où en sont les bénévoles, et où en est
 * le remplissage des créneaux.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly EditionOverview $overview) {}

    public function __invoke(): View
    {
        $edition = Edition::current();

        return view('admin.dashboard', [
            'edition' => $edition,
            'headcount' => $edition === null ? null : $this->overview->headcount($edition),
            'fillRate' => $edition === null ? null : $this->overview->fillRate($edition),
            'byDay' => $edition === null ? collect() : $this->overview->fillRateByDay($edition),
            'byMission' => $edition === null ? collect() : $this->overview->fillRateByMission($edition),
        ]);
    }
}
