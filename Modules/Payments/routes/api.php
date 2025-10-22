<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\PaymeController;
use Modules\Payments\Http\Controllers\ClickController;

Route::prefix('payme')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('booking', [PaymeController::class, 'booking']);
    });
    Route::post('callback', [PaymeController::class, 'handleCallback']);
});

Route::prefix('payment/click')->group(function () {
    Route::post('/prepare', [ClickController::class, 'prepare']);
    Route::post('/complete', [ClickController::class, 'complete']);
});
