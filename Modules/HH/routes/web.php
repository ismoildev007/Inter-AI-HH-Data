<?php

use Illuminate\Support\Facades\Route;
use Modules\HH\Http\Controllers\HHController;
use Modules\HH\Http\Controllers\CandidateSearchController;

Route::
//middleware(['auth', 'verified'])->
prefix('hh')->name('hh.')->group(function () {
    // Existing routes if any, let's keep them under a different prefix if needed
    // Route::resource('hhs', HHController::class)->names('hh');

    // Candidate Search Routes
    Route::get('search', [CandidateSearchController::class, 'create'])->name('search.create');
    Route::post('search', [CandidateSearchController::class, 'store'])->name('search.store');
    Route::get('search/processing', [CandidateSearchController::class, 'processing'])->name('search.processing');
    Route::get('search/results/{searchRequest}', [CandidateSearchController::class, 'results'])->name('search.results');
});
