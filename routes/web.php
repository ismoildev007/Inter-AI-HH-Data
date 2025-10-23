<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('admin.login');
});

Route::get('/set-commands', function (Modules\TelegramBot\Services\TelegramBotService $botService) {
    $botService->setBotCommands();
    return 'Commands set successfully!';
});

Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json([
            'message' => 'Resource not found.',
        ], 404);
    }

    return response()->view('errors.404', [], 404);
});
