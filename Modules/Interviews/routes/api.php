<?php

use Illuminate\Support\Facades\Route;
use Modules\Interviews\Http\Controllers\InterviewsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('interviews', InterviewsController::class)->names('interviews');
});
