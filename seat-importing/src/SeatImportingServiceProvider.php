<?php

namespace Apokavkos\SeatImporting;

use Illuminate\Support\ServiceProvider;
use Apokavkos\SeatImporting\Console\Commands\ImportMarketData;
use Apokavkos\SeatImporting\Services\MarketMetricsService;

class SeatImportingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        }

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'seat-importing');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'seat-importing');

        $this->publishes([
            __DIR__ . '/../config/seat-importing.php' => config_path('seat-importing.php'),
        ], 'seat-importing-config');

        if ($this->app->runningInConsole()) {
            $this->commands([ImportMarketData::class]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/seat-importing.php', 'seat-importing');

        $this->app->singleton(MarketMetricsService::class);

        $this->registerMenuEntries();
    }

    protected function registerMenuEntries(): void
    {
        if (file_exists(__DIR__ . '/Menu/package.sidebar.php')) {
            $this->mergeConfigFrom(__DIR__ . '/Menu/package.sidebar.php', 'package.sidebar');
        }
    }
}
