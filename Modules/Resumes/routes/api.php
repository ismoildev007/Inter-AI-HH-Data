<?php

use Illuminate\Support\Facades\Route;
use Modules\Resumes\Http\Controllers\ResumesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('resumes', ResumesController::class)->names('resumes');
});
