<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\DirectorDashboardController;
use App\Http\Controllers\ScheduleController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'index'])->name('api.user');
    
    // API-specific routes for dashboard stats and attendance alerts
    Route::middleware('role:Directeur|Secrétaire')->group(function () {
        Route::get('/stats/director', [DirectorDashboardController::class, 'apiStats'])
            ->name('api.stats.director');
        Route::get('/stats/director/learner-absences/{user}/{group}', [DirectorDashboardController::class, 'getLearnerAbsences'])
            ->name('api.stats.director.learner-absences');
        Route::get('/attendance/pending-alerts', [ScheduleController::class, 'pendingAlerts'])
            ->name('api.attendance.pending-alerts');
    });
});
