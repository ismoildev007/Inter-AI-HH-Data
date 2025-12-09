<?php

use Illuminate\Support\Facades\Route;
use Modules\ResumeCreate\Http\Controllers\ResumeCreateController;

// Barcha endpointlar token yoki cookie orqali auth qilinadi (controller ichida resolveUserFromRequest)
Route::prefix('v1')->middleware('query.token')->group(function () {
    Route::get('resume-create/ping', [ResumeCreateController::class, 'ping'])->name('resumecreate.ping');

    Route::get('resume-create', [ResumeCreateController::class, 'show'])->name('resumecreate.show');
    Route::post('resume-create', [ResumeCreateController::class, 'store'])->name('resumecreate.store');

    Route::post('resume-create/photo', [ResumeCreateController::class, 'uploadPhoto'])->name('resumecreate.photo.upload');
    Route::delete('resume-create/photo', [ResumeCreateController::class, 'deletePhoto'])->name('resumecreate.photo.delete');

    Route::get('resume-create/pdf', [ResumeCreateController::class, 'downloadPdf'])->name('resumecreate.pdf');
    Route::post('resume-create/pdf/send-to-telegram', [ResumeCreateController::class, 'sendPdfToTelegram'])->name('resumecreate.pdf.telegram');
    Route::get('resume-create/docx', [ResumeCreateController::class, 'downloadDocx'])->name('resumecreate.docx');
    Route::post('resume-create/docx/send-to-telegram', [ResumeCreateController::class, 'sendDocxToTelegram'])->name('resumecreate.docx.telegram');
});
