<?php

namespace Apokavkos\SeatImporting;

use Seat\Services\AbstractSeatPlugin;
use Apokavkos\SeatImporting\Console\Commands\ImportMarketData;
use Apokavkos\SeatImporting\Services\MarketMetricsService;
use Illuminate\Console\Scheduling\Schedule;

class SeatImportingServiceProvider extends AbstractSeatPlugin
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'seat-importing');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([ImportMarketData::class]);

            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('seat:importing:import --download')->dailyAt('04:00');
            });
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Menu/package.sidebar.php', 'package.sidebar');
        $this->registerPermissions(__DIR__ . '/Config/Permissions/market.permissions.php', 'market');
        
        $this->app->singleton(MarketMetricsService::class);
    }

    public function getName(): string { return "Market Importing"; }
    public function getPackageRepositoryUrl(): string { return 'https://github.com/apokavkos/mohrg'; }
    public function getPackagistPackageName(): string { return 'apokavkos/seat-importing'; }
    public function getPackagistVendorName(): string { return 'apokavkos'; }
}
