<?php

use Illuminate\Support\Facades\Route;
use Modules\DemoResume\Http\Controllers\DemoResumeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('demoresumes', DemoResumeController::class)->names('demoresume');
});
