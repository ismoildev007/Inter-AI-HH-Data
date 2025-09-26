<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\UserController;
use Modules\Admin\Http\Controllers\ResumeController;
use Modules\Admin\Http\Controllers\ApplicationController;
use Modules\Admin\Http\Controllers\TelegramChannelController;
use Modules\Admin\Http\Controllers\ProfileController;

Route::prefix('admin')->name('admin.')->group(function () {
    // Auth pages (no UI changes here)
    Route::get('login', [AdminController::class, 'index'])->name('login')->middleware('guest');
    Route::post('login', [AdminController::class, 'login'])->name('login.attempt')->middleware('guest');
    // register removed per request
    Route::get('logout', [AdminController::class, 'logout'])->name('logout');

    // Protected admin pages
    Route::middleware(['auth'])->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get('profile', [ProfileController::class, 'index'])->name('profile');

        // Users
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{id}', [UserController::class, 'show'])->name('users.show');

        // Resumes
        Route::get('resumes', [ResumeController::class, 'index'])->name('resumes.index');
        Route::get('resumes/{id}', [ResumeController::class, 'show'])->name('resumes.show');

        // Applications
        Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{id}', [ApplicationController::class, 'show'])->name('applications.show');

        // Telegram Channels
        Route::get('telegram-channels', [TelegramChannelController::class, 'index'])->name('telegram_channels.index');
        Route::get('telegram-channels/create', [TelegramChannelController::class, 'create'])->name('telegram_channels.create');
        Route::post('telegram-channels', [TelegramChannelController::class, 'store'])->name('telegram_channels.store');
        Route::delete('telegram-channels/{channel}', [TelegramChannelController::class, 'destroy'])->name('telegram_channels.destroy');
    });
});
