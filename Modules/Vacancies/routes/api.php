<?php

use Illuminate\Support\Facades\Route;
use Modules\Vacancies\Http\Controllers\VacanciesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('vacancies', VacanciesController::class)->names('vacancies');
});
