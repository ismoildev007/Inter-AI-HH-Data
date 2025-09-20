<?php

namespace Modules\TelegramChannel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class TelegramChannelServiceProvider extends ServiceProvider
{

    protected string $name = 'TelegramChannel';
    protected string $nameLower = 'telegramchannel';

    public function boot(): void
    {
        $this->registerTranslations();
        //
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->registerCommands();

        // scheduler no longer used for scanning; scan-loop daemon handles dispatching

        // Register scheduler for short-running relay (idempotent, safe to run in prod)
        $this->app->booted(function () {
            try {
                /** @var Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);
                // Run one relay scan per minute, avoid overlapping runs
                $schedule->command('relay:run --once')->everyMinute()->withoutOverlapping();
            } catch (\Throwable $e) {
                // Scheduling is best-effort; avoid failing the app boot
            }
        });
    }

    public function register(): void
    {
        $this->registerConfig();
        $this->app->register(RouteServiceProvider::class);

        // Reuse a single MadelineClient instance per PHP process (queue worker)
        $this->app->singleton(\Modules\TelegramChannel\Services\Telegram\MadelineClient::class, function () {
            return new \Modules\TelegramChannel\Services\Telegram\MadelineClient();
        });
    }

protected function registerConfig(): void
{
    $base  = __DIR__ . '/../config/config.php';
    $relay = __DIR__ . '/../config/relay.php';

    // 1) Runtime uchun MERGE (cache mavjud bo'lsa ham muammo bo'lmaydi)
    if (is_file($base)) {
        $this->mergeConfigFrom($base,  $this->nameLower);
    }
    if (is_file($relay)) {
        $this->mergeConfigFrom($relay, $this->nameLower.'_relay');
    }

    // 2) PUBLISH faqat konsolda e'lon qilinadi (package:discover davrida ham OK)
    if ($this->app->runningInConsole()) {
        if (is_file($base)) {
            $this->publishes([$base  => config_path($this->nameLower.'.php')], 'config');
        }
        if (is_file($relay)) {
            $this->publishes([$relay => config_path($this->nameLower.'_relay.php')], 'config');
        }
    }
}

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $moduleLang = __DIR__ . '/../lang';
            if (is_dir($moduleLang)) {
                $this->loadTranslationsFrom($moduleLang, $this->nameLower);
                $this->loadJsonTranslationsFrom($moduleLang);
            }
        }
    }



    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = __DIR__ . '/../resources/views';

        if ($this->app->runningInConsole() && is_dir($sourcePath)) {
            $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);
        }
        if (is_dir($sourcePath)) {
            $this->loadViewsFrom([$sourcePath], $this->nameLower);
        }
    }

    protected function registerCommands(): void
    {
            if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\TelegramChannel\Console\Commands\TelegramLoginCommand::class,
                \Modules\TelegramChannel\Console\Commands\RelayRunCommand::class,
            ]);
        }
    }
}
