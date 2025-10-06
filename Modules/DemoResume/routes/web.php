<?php

use Illuminate\Support\Facades\Route;
use Modules\DemoResume\Http\Controllers\DemoResumeController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('demoresumes', DemoResumeController::class)->names('demoresume');
});
