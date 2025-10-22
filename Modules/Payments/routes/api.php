<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\PaymeController;
use Modules\Payments\Http\Controllers\ClickController;

Route::middleware(['auth:sanctum'])->prefix('payme')->group(function () {
    Route::post('callback', [PaymeController::class, 'handleCallback']);
    Route::get('booking', [PaymeController::class, 'booking']);
 });

Route::prefix('payment/click')->group(function () {
    Route::post('/prepare', [ClickController::class, 'prepare']);
    Route::post('/complete', [ClickController::class, 'complete']);
});
