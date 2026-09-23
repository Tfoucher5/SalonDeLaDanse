<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\LegalNoticeController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PlanningSummaryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShiftBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mentions-legales', LegalNoticeController::class)->name('legal.notice');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// La grille reste consultable en permanence : seule l ecriture du planning
// passe par le middleware 'registration.open'.
Route::get('/planning', PlanningController::class)
    ->middleware(['auth', 'verified'])
    ->name('planning.index');

// La fiche recapitulative se consulte et s imprime a tout moment, brouillon
// compris : elle ne fait que restituer ce que le benevole a deja retenu.
Route::get('/planning/fiche', PlanningSummaryController::class)
    ->middleware(['auth', 'verified'])
    ->name('planning.summary');

// Composer son planning suppose la fenetre ouverte et le planning non verrouille :
// 'registration.open' coupe court, PlanningRules reste l autorite sur le reste.
Route::middleware(['auth', 'verified', 'registration.open'])->group(function () {
    Route::post('/planning/shifts/{shift}', [ShiftBookingController::class, 'store'])
        ->name('planning.shifts.store');
    Route::delete('/planning/shifts/{shift}', [ShiftBookingController::class, 'destroy'])
        ->name('planning.shifts.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// La planche de la charte graphique est un outil de travail : elle n existe
// jamais en production, et jamais pour un visiteur non connecte.
if (! app()->isProduction()) {
    Route::get('/design-system', DesignSystemController::class)
        ->middleware('auth')
        ->name('design-system');
}

require __DIR__.'/auth.php';
