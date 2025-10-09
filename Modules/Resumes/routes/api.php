<?php

use Illuminate\Support\Facades\Route;
use Modules\Resumes\Http\Controllers\HhResumeController;
use Modules\Resumes\Http\Controllers\ResumesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    Route::apiResource('resumes', ResumesController::class)->names('resumes');

    Route::patch('resumes/{id}/primary', [ResumesController::class, 'setPrimary'])->name('resumes.setPrimary');

    Route::get('hh-resumes/my', [HhResumeController::class, 'myHhResumes'])->name('hh-resumes.my');

    Route::post('hh-resumes/{resumeId}/set-primary' , [HhResumeController::class, 'saveAsPrimary'])->name('hh-resumes.setAsPrimary');
});

Route::post('v1/demo/resume/store', [ResumesController::class, 'demoStore'])->name('demo.resumes.store');
