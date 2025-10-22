<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\ClickController;
use Modules\Payments\Http\Controllers\PaymentsController;

// Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
//     Route::apiResource('payments', PaymentsController::class)->names('payments');
// });

Route::prefix('payment/click')->group(function () {
    Route::post('/prepare', [ClickController::class, 'prepare']);
    Route::post('/complete', [ClickController::class, 'complete']);
});
