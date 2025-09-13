<?php

use Illuminate\Support\Facades\Route;
use Modules\Vacancies\Http\Controllers\VacanciesController;
use Modules\Vacancies\Http\Controllers\HHVacancyController;
use Modules\Vacancies\Http\Controllers\VacancyMatchingController;

Route::prefix('v1')
    ->name('v1.')
    ->group(function () {
        
        /**
         * CRUD for internal vacancies (our DB)
         */
        Route::apiResource('vacancies', VacanciesController::class)
            ->names('vacancies');

        /**
         * Resume → Vacancy matching
         */
        Route::middleware(['auth:sanctum'])->prefix('resumes/{resumeId}')
            ->name('resumes.')
            ->group(function () {
                Route::post('match', [VacancyMatchingController::class, 'match'])
                    ->name('match');
            });

        /**
         * HeadHunter API integration
         */
        Route::prefix('hh')
            ->name('hh.')
            ->group(function () {
                Route::get('vacancies', [HHVacancyController::class, 'index'])
                    ->name('vacancies.index'); 

                Route::get('vacancies/{id}', [HHVacancyController::class, 'show'])
                    ->name('vacancies.show'); 
            });
    });
