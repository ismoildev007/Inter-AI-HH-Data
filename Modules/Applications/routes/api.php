<?php

use Illuminate\Support\Facades\Route;
use Modules\Applications\Http\Controllers\ApplicationsController;

// Existing Applications resource (kept as-is)
Route::apiResource('applications', ApplicationsController::class)->names('applications');

// New HH negotiations endpoint under /api/v1/hh/negotiations (auth required)
Route::prefix('v1')
    ->name('v1.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('hh/negotiations', [ApplicationsController::class, 'negotiations'])
            ->name('hh.negotiations.index');
    });
