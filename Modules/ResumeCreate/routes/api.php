<?php

use Illuminate\Support\Facades\Route;
use Modules\ResumeCreate\Http\Controllers\ResumeCreateController;

// Authenticated API for wizard
Route::middleware(['query.token', 'auth:sanctum'])
    ->prefix('v1')
    ->group(function () {
        Route::get('resume-create/ping', [ResumeCreateController::class, 'ping'])->name('resumecreate.ping');

        Route::get('resume-create', [ResumeCreateController::class, 'show'])->name('resumecreate.show');
        Route::post('resume-create', [ResumeCreateController::class, 'store'])->name('resumecreate.store');

        Route::post('resume-create/photo', [ResumeCreateController::class, 'uploadPhoto'])->name('resumecreate.photo.upload');
        Route::delete('resume-create/photo', [ResumeCreateController::class, 'deletePhoto'])->name('resumecreate.photo.delete');
    });

// PDF download: token yoki cookie orqali authenticate qilamiz (ichkarida)
Route::get('v1/resume-create/pdf', [ResumeCreateController::class, 'downloadPdf'])
    ->middleware('query.token')
    ->name('resumecreate.pdf');

// PDF ni Telegram chatiga fayl sifatida yuborish
Route::post('v1/resume-create/pdf/send-to-telegram', [ResumeCreateController::class, 'sendPdfToTelegram'])
    ->middleware('query.token')
    ->name('resumecreate.pdf.telegram');
