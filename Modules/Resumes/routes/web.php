<?php

use Illuminate\Support\Facades\Route;
use Modules\Resumes\Http\Controllers\ResumesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('resumes', ResumesController::class)->names('resumes');
});
