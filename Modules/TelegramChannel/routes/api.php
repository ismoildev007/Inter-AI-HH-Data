<?php

use Illuminate\Support\Facades\Route;
use Modules\TelegramChannel\Http\Controllers\ChannelsController;

Route::prefix('v1')->group(function () {
    // Simple listing/creation endpoints for channels (can be restricted later)
    Route::get('telegram/channels', [ChannelsController::class, 'index']);
    Route::post('telegram/channels', [ChannelsController::class, 'store']);
});

