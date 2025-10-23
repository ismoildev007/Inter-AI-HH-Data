<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\UsersController;
use Modules\Users\Http\Controllers\AuthController;
use Modules\Users\Http\Controllers\HhAccountsController;

Route::post('/user-verify', [AuthController::class, 'userVerify']);
Route::post('/sending-code', [AuthController::class, 'requestVerificationCode']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/demo-resume-check', [AuthController::class, 'resumeCheck']);

// Public OAuth endpoints (no auth required yet)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('cover-letter', [AuthController::class, 'coverLetter']);
    Route::put('cover-letter', [AuthController::class, 'updateCoverLetter']);
    Route::get('hh-accounts/authorize', [HhAccountsController::class, 'authorizeUrl']);
    Route::get('hh-accounts/callback', [HhAccountsController::class, 'callback']);
    Route::get('balance', [AuthController::class, 'balance']);
    Route::get('hh-accounts/me', [HhAccountsController::class, 'me']);
    Route::post('hh-accounts/attach', [HhAccountsController::class, 'attach']);
    Route::delete('hh-accounts/me', [HhAccountsController::class, 'disconnect']);
    Route::post('hh-accounts/refresh', [HhAccountsController::class, 'refreshToken']);
});


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('chat-id-login', [AuthController::class, 'chatIdLogin'])->name('chat.id.login');
    Route::get('check-token', [AuthController::class, 'checkToken']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::match(['put', 'patch'], 'users/{id}', [AuthController::class, 'update']);

        // Authenticated endpoints (will work when user/auth ready)

        // Auto-apply settings (authenticated user)
        Route::get('settings/auto-apply', [AuthController::class, 'getAutoApply']);
        Route::post('settings/auto-apply', [AuthController::class, 'createAutoApply']);
        Route::patch('settings/auto-apply', [AuthController::class, 'updateAutoApply']);

        // User Worked status Update

        Route::post('worked-status-update', [UsersController::class, 'workedStatusUpdate']);

    });
});
