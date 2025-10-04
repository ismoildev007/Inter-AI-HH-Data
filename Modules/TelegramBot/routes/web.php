<?php

use Illuminate\Support\Facades\Route;
use Modules\TelegramBot\Http\Controllers\TelegramBotController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('telegrambots', TelegramBotController::class)->names('telegrambot');
});
