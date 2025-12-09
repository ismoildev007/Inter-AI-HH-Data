<?php

use Illuminate\Support\Facades\Route;
use Modules\Interviews\Http\Controllers\InterviewsController;
use Modules\Interviews\Http\Controllers\MockInterviewController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('interviews', InterviewsController::class)->names('interviews');
    Route::post('generate-questions', [MockInterviewController::class, 'generateQuestions'])->name('interviews.generate-questions');
    Route::get('check-resume-eligibility', [MockInterviewController::class, 'checkResumeEligibility'])->name('interviews.check-resume-eligibility');
    Route::get('mock-interviews', [MockInterviewController::class, 'getMockInterview'])->name('interviews.mock-interviews');
});
