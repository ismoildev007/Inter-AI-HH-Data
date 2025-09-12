<?php

use Illuminate\Support\Facades\Route;
use Modules\Vacancies\Http\Controllers\VacanciesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('vacancies', VacanciesController::class)->names('vacancies');
});
