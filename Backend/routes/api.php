<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\DirectorDashboardController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'index'])->name('api.user');
    
    // API-specific routes for dashboard stats
    Route::prefix('stats')->middleware('role:Directeur|Secrétaire')->group(function () {
        Route::get('/director', [DirectorDashboardController::class, 'apiStats'])
            ->name('api.stats.director');
        Route::get('/director/learner-absences/{user}/{group}', [DirectorDashboardController::class, 'getLearnerAbsences'])
            ->name('api.stats.director.learner-absences');
    });
});
