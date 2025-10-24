<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\BillingDashboardController;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\UserController;
use Modules\Admin\Http\Controllers\ResumeController;
use Modules\Admin\Http\Controllers\ApplicationController;
use Modules\Admin\Http\Controllers\TelegramChannelController;
use Modules\Admin\Http\Controllers\ProfileController;
use Modules\Admin\Http\Controllers\LocaleController;
use Modules\Admin\Http\Controllers\PlanController;
use Modules\Admin\Http\Controllers\SubscriptionController;
use Modules\Admin\Http\Controllers\TransactionController;

Route::prefix('admin')->name('admin.')->group(function () {
    // Auth pages (no UI changes here)
    Route::get('login', [AdminController::class, 'index'])->name('login')->middleware('guest');
    Route::post('login', [AdminController::class, 'login'])->name('login.attempt')->middleware('guest');
    // register removed per request
    Route::get('logout', [AdminController::class, 'logout'])->name('logout');

    // Protected admin pages
    // TrackVisits middleware here to record authenticated admin user page hits
    Route::middleware(['auth.admin'])->group(function () {
        // Localization
        Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/billing', [BillingDashboardController::class, 'index'])->name('dashboard.billing');
        Route::get('/visits/top-users', [DashboardController::class, 'topVisitors'])->name('visits.top_users');
        Route::get('/vacancies/categories', [DashboardController::class, 'vacancyCategories'])->name('vacancies.categories');

        // Profile
        Route::get('profile', [ProfileController::class, 'index'])->name('profile');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Users
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/admin-check', [UserController::class, 'adminCheck'])->name('users.admin_check');
        Route::post('users/admin-check/{user}/mark-not-working', [UserController::class, 'adminCheckMarkNotWorking'])->name('users.admin_check.mark_not_working');
        Route::post('users/admin-check/{user}/verify', [UserController::class, 'adminCheckVerify'])->name('users.admin_check.verify');
        Route::get('users/admin-check/{user}', [UserController::class, 'adminCheckShow'])->name('users.admin_check.show');
        Route::get('users/{id}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/vacancies', [UserController::class, 'vacancies'])->name('users.vacancies.index');
        Route::get('users/{user}/subscriptions', [UserController::class, 'subscriptions'])->name('users.subscriptions.index');
        Route::get('users/{user}/transactions', [UserController::class, 'transactions'])->name('users.transactions.index');

        // Resumes
        Route::get('resumes', [ResumeController::class, 'index'])->name('resumes.index');
        Route::get('resumes/{id}', [ResumeController::class, 'show'])->name('resumes.show');
        Route::get('resumes/{id}/download', [ResumeController::class, 'download'])->name('resumes.download');

        // Applications
        Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{id}', [ApplicationController::class, 'show'])->name('applications.show');

        // Telegram Channels
        Route::get('telegram-channels', [TelegramChannelController::class, 'index'])->name('telegram_channels.index');
        Route::get('telegram-channels/create', [TelegramChannelController::class, 'create'])->name('telegram_channels.create');
        Route::post('telegram-channels', [TelegramChannelController::class, 'store'])->name('telegram_channels.store');
        Route::delete('telegram-channels/{channel}', [TelegramChannelController::class, 'destroy'])->name('telegram_channels.destroy');

        // Plans
        Route::resource('plans', PlanController::class)->parameters([
            'plans' => 'plan',
        ]);

        // Subscriptions
        Route::resource('subscriptions', SubscriptionController::class)
            ->only(['index', 'show'])
            ->parameters([
                'subscriptions' => 'subscription',
            ]);

        // Transactions
        Route::resource('transactions', TransactionController::class)
            ->only(['index', 'show'])
            ->parameters([
                'transactions' => 'transaction',
            ]);

        // Vacancies by Category → list titles for a category
        Route::get('vacancies/category/{category}', [DashboardController::class, 'vacanciesByCategory'])->name('vacancies.by_category');
        Route::patch('vacancies/{vacancy}/category', [DashboardController::class, 'vacancyUpdateCategory'])->name('vacancies.update_category');
        Route::patch('vacancies/{vacancy}/status', [DashboardController::class, 'vacancyUpdateStatus'])->name('vacancies.update_status');
        Route::delete('vacancies/{vacancy}', [DashboardController::class, 'vacancyDestroy'])->name('vacancies.destroy');
        // Vacancy details (single)
        Route::get('vacancies/{id}', [DashboardController::class, 'vacancyShow'])->name('vacancies.show');
    });
});
