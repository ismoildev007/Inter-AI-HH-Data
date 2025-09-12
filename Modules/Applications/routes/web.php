<?php

use Illuminate\Support\Facades\Route;
use Modules\Applications\Http\Controllers\ApplicationsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('applications', ApplicationsController::class)->names('applications');
});
