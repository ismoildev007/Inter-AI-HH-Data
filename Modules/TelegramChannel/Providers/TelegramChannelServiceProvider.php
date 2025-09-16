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

        // scheduler no longer used for scanning; scan-loop daemon handles dispatching
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
                \Modules\TelegramChannel\Console\Commands\TelegramScanLoopCommand::class,
                \Modules\TelegramChannel\Console\Commands\TelegramOrchestratorCommand::class,
                \Modules\TelegramChannel\Console\Commands\TelegramTestSendCommand::class,
            ]);
        }
    }
}
