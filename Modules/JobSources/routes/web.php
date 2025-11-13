<?php

use Illuminate\Support\Facades\Route;
use Modules\JobSources\Http\Controllers\JobSourcesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('jobsources', JobSourcesController::class)->names('jobsources');
});
