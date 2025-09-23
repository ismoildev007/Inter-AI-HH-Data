<?php

use Illuminate\Support\Facades\Route;
use Modules\Applications\Http\Controllers\ApplicationsController;

// All Applications API routes under /api/v1/* and protected by Sanctum
Route::prefix('v1')
    ->name('v1.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // Auth user's applications list (JSON)
        Route::get('applications', [ApplicationsController::class, 'index'])
            ->name('applications.index');

        // HH negotiations passthrough for the authenticated user
        Route::get('hh/negotiations', [ApplicationsController::class, 'negotiations'])
            ->name('hh.negotiations.index');
    });
