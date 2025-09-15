<?php

namespace Modules\TelegramChannel\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Illuminate\Console\Scheduling\Schedule;

class TelegramChannelServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'TelegramChannel';
    protected string $nameLower = 'telegramchannel';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerCommands();

        // Register scheduler for continuous scan-dispatch
        $this->app->booted(function () {
            if (!config('telegramchannel.schedule_enabled', true)) {
                return;
            }
            $interval = (int) config('telegramchannel.scan_interval_seconds', 15);
            $schedule = $this->app->make(Schedule::class);
            $event = $schedule->command('telegram:scan-dispatch');

            // Map seconds to scheduler granularity (Laravel 12 supports sub-minute)
            if ($interval <= 1) {
                $event->everySecond();
            } elseif ($interval <= 5) {
                $event->everyFiveSeconds();
            } elseif ($interval <= 10) {
                $event->everyTenSeconds();
            } elseif ($interval <= 15) {
                $event->everyFifteenSeconds();
            } elseif ($interval <= 30) {
                $event->everyThirtySeconds();
            } else {
                $event->everyMinute();
            }

            $event->withoutOverlapping();
        });
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, 'config/config.php');
        $this->publishes([$configPath => config_path($this->nameLower.'.php')], 'config');
        $this->mergeConfigFrom($configPath, $this->nameLower);
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);
        $this->loadViewsFrom([$sourcePath], $this->nameLower);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\TelegramChannel\Console\Commands\TelegramLoginCommand::class,
                \Modules\TelegramChannel\Console\Commands\TelegramRelayCommand::class,
                \Modules\TelegramChannel\Console\Commands\TelegramScanDispatchCommand::class,
            ]);
        }
    }
}
