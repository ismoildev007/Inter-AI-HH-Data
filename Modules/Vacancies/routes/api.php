<?php

use Illuminate\Support\Facades\Route;
use Modules\DemoResume\Http\DemoVacancyMatchingController;
use Modules\Vacancies\Http\Controllers\HHVacancyController;
use Modules\Vacancies\Http\Controllers\VacanciesController;
use Modules\Vacancies\Http\Controllers\VacancyMatchingController;

Route::prefix('v1')
    ->name('v1.')
    ->group(function () {
        Route::middleware(['auth:sanctum'])->group(
            function () {
                Route::post('hh/vacancies/{vacancy}/apply', [HHVacancyController::class, 'apply'])->name('hh.vacancies.apply');
                Route::get('/telegram/vacancies/{id}', [HHVacancyController::class, 'telegramShow']);
            }
        );
        /**
         * CRUD for internal vacancies (our DB)
         */
        Route::apiResource('vacancies', VacanciesController::class)
            ->names('vacancies');
        Route::get('hhsearch', [HHVacancyController::class, 'hhSearch'])
            ->name('hh.search');
        /**
         * Resume → Vacancy matching
         */
        Route::middleware(['auth:sanctum'])
            ->prefix('vacancy-matches')
            ->name('vacancy-matches.')
            ->group(function () {
                Route::post('run', [VacancyMatchingController::class, 'match'])
                    ->name('run');
                Route::get('/', [VacancyMatchingController::class, 'myMatches'])
                    ->name('index');
            });
        /**
         * Demo → Vacancy matching
         */

        Route::prefix('demo/vacancy-matches')
            ->name('demo.vacancy-matches.')
            ->group(function () {
                Route::post('run', [DemoVacancyMatchingController::class, 'match'])
                    ->name('run');
                Route::get('/', [DemoVacancyMatchingController::class, 'myMatches'])
                    ->name('index');
            });
        /**
         * HeadHunter API integration
         */
        Route::middleware(['auth:sanctum'])->prefix('hh')
            ->name('hh.')
            ->group(function () {
                Route::get('vacancies', [HHVacancyController::class, 'index'])
                    ->name('vacancies.index');

                Route::get('vacancies/{id}', [HHVacancyController::class, 'show'])
                    ->name('vacancies.show');
            });

    });
