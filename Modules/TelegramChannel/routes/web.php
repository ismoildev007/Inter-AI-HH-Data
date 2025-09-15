<?php

use Illuminate\Support\Facades\Route;
use Modules\TelegramChannel\Http\Controllers\ChannelsPageController;

Route::prefix('telegram/channels')->name('telegram.channels.')->group(function () {
    Route::get('manage', [ChannelsPageController::class, 'index'])->name('index');
    Route::get('create', [ChannelsPageController::class, 'create'])->name('create');
    Route::post('', [ChannelsPageController::class, 'store'])->name('store');
});
