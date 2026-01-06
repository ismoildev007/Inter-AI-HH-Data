<?php

use Illuminate\Support\Facades\Route;
use Modules\HH\Http\Controllers\HHController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('hhs', HHController::class)->names('hh');
});
