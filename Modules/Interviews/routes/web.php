<?php

use Illuminate\Support\Facades\Route;
use Modules\Interviews\Http\Controllers\InterviewsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('interviews', InterviewsController::class)->names('interviews');
});
