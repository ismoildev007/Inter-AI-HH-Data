<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('admin.login');
});

//Route::get('/set-commands', function (Modules\TelegramBot\Services\TelegramBotService $botService) {
//    $botService->setBotCommands();
//    return 'Commands set successfully!';
//});

Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json([
            'message' => 'Resource not found.',
        ], 404);
    }

    return response()->view('errors.404', [], 404);
});


Route::get('info-test', function () {
    return php_sapi_name();
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/logs', function () {
        return redirect()->to('/log-viewer');
    });
});

