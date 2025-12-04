<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('viewLogViewer', function ($user) {
            // Faqat adminlar kirishi uchun:
            return ($user?->is_admin ?? false) === true;
            // yoki o'zingdagi rol sistemaga moslab o'zgartir
        });
    }
}
