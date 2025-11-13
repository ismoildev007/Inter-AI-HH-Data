<?php

use Illuminate\Support\Facades\Route;
use Modules\JobSources\Http\Controllers\JobSourcesController;


Route::get('/jobsources/fetchIndeed', [JobSourcesController::class, 'fetchIndeed'])->name('jobsources.fetch');
Route::get('/jobsources/linkedin', [JobSourcesController::class, 'linkedin']);


Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('jobsources', JobSourcesController::class)->names('jobsources');
});
