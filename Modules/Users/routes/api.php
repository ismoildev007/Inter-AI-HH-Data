<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\UsersController;
use Modules\Users\Http\Controllers\AuthController;
use Modules\Users\Http\Controllers\HhAccountsController;

// Public OAuth endpoints (no auth required yet)
Route::prefix('v1')->group(function () {
    Route::get('hh-accounts/authorize', [HhAccountsController::class, 'authorizeUrl']);
    Route::get('hh-accounts/callback', [HhAccountsController::class, 'callback']);
});


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        // Authenticated endpoints (will work when user/auth ready)

        Route::apiResource('users', UsersController::class)->names('users');
        Route::get('hh-accounts/me', [HhAccountsController::class, 'me']);
        Route::post('hh-accounts/attach', [HhAccountsController::class, 'attach']);
        Route::delete('hh-accounts/me', [HhAccountsController::class, 'disconnect']);
    });
});
