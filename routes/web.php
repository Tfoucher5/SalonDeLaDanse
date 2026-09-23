<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExportController as AdminExportController;
use App\Http\Controllers\Admin\MissionController as AdminMissionController;
use App\Http\Controllers\Admin\PlanningController as AdminPlanningController;
use App\Http\Controllers\Admin\VolunteerController as AdminVolunteerController;
use App\Http\Controllers\Admin\VolunteerCredentialsController as AdminVolunteerCredentialsController;
use App\Http\Controllers\Admin\VolunteerPlanningController as AdminVolunteerPlanningController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PlanningSummaryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShiftBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Espace benevole. 'volunteer.space' renvoie l administrateur chez lui : ces
// ecrans ne lui montreraient qu un planning qu il n a pas a composer.
Route::middleware(['auth', 'verified', 'volunteer.space'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // La grille reste consultable en permanence : seule l ecriture du planning
    // passe par le middleware 'registration.open'.
    Route::get('/planning', PlanningController::class)->name('planning.index');

    // La fiche recapitulative se consulte et s imprime a tout moment, brouillon
    // compris : elle ne fait que restituer ce que le benevole a deja retenu.
    Route::get('/planning/fiche', PlanningSummaryController::class)->name('planning.summary');

    // Composer son planning suppose la fenetre ouverte et le planning non verrouille :
    // 'registration.open' coupe court, PlanningRules reste l autorite sur le reste.
    Route::middleware('registration.open')->group(function () {
        Route::post('/planning/shifts/{shift}', [ShiftBookingController::class, 'store'])
            ->name('planning.shifts.store');
        Route::delete('/planning/shifts/{shift}', [ShiftBookingController::class, 'destroy'])
            ->name('planning.shifts.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Back-office : la porte `admin` est la seule autorisation du groupe. Un
// benevole authentifie qui pousse la porte recoit un 403, pas une redirection.
Route::middleware(['auth', 'verified', 'can:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        // Le planning global, en lecture seule : chaque jour, chaque creneau,
        // et le nom de qui y est inscrit. On modifie depuis la fiche.
        Route::get('/planning', AdminPlanningController::class)->name('planning');

        // Le catalogue des missions. `MissionCatalogue` porte les deux refus
        // qui touchent aux inscriptions : baisser une jauge sous ce qui est
        // attribue, et supprimer une mission pourvue.
        Route::resource('missions', AdminMissionController::class)->except(['show']);

        Route::get('/volunteers', [AdminVolunteerController::class, 'index'])->name('volunteers.index');
        Route::get('/volunteers/{volunteer}', [AdminVolunteerController::class, 'show'])->name('volunteers.show');

        // Outrepassement : l administrateur ecrit la ou le benevole ne peut
        // plus rien. Les regles restent dans PlanningRules, qui decide seule de
        // ce qui demeure impossible.
        Route::get('/volunteers/{volunteer}/edit', [AdminVolunteerController::class, 'edit'])->name('volunteers.edit');
        Route::patch('/volunteers/{volunteer}', [AdminVolunteerController::class, 'update'])->name('volunteers.update');

        Route::post('/volunteers/{volunteer}/credentials', AdminVolunteerCredentialsController::class)
            ->name('volunteers.credentials');

        // La validation definitive part d ici, et de nulle part ailleurs : elle
        // appartient a l organisation, pas au benevole.
        Route::post('/volunteers/{volunteer}/validate', [AdminVolunteerPlanningController::class, 'validate'])
            ->name('volunteers.validate');
        Route::post('/volunteers/{volunteer}/unlock', [AdminVolunteerPlanningController::class, 'unlock'])
            ->name('volunteers.unlock');
        Route::post('/volunteers/{volunteer}/shifts', [AdminVolunteerPlanningController::class, 'store'])
            ->name('volunteers.shifts.store');
        Route::delete('/volunteers/{volunteer}/shifts/{shift}', [AdminVolunteerPlanningController::class, 'destroy'])
            ->name('volunteers.shifts.destroy');

        // Exports. Feuille et format sont des enumerations liees par la route :
        // une valeur inconnue rend un 404 avant d atteindre le controleur.
        Route::get('/exports', [AdminExportController::class, 'index'])->name('exports.index');
        Route::get('/exports/{dataset}/{format}', [AdminExportController::class, 'download'])->name('exports.download');
    });

// La planche de la charte graphique est un outil de travail : elle n existe
// jamais en production, et jamais pour un visiteur non connecte.
if (! app()->isProduction()) {
    Route::get('/design-system', DesignSystemController::class)
        ->middleware('auth')
        ->name('design-system');
}

require __DIR__.'/auth.php';
