<?php

use Illuminate\Support\Facades\Route;
use Modules\ResumeCreate\Http\Controllers\ResumeCreateController;

Route::middleware(['auth:sanctum'])
    ->prefix('v1')
    ->group(function () {
        // ResumeCreate API endpoints will be defined here based on Figma design
        Route::get('resume-create/ping', [ResumeCreateController::class, 'ping'])->name('resumecreate.ping');
    });

