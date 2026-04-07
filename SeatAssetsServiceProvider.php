<?php

namespace Apokavkos\SeatAssets;

use Illuminate\Support\ServiceProvider;

class SeatAssetsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        // ── Routes ──────────────────────────────────────────────
        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        }

        // ── Views ──────────────────────────────────────────────
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'seat-assets');

        // ── Migrations ─────────────────────────────────────────
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // ── Translations ────────────────────────────────────────
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'seat-assets');

        // ── Config Publishing ──────────────────────────────────
        $this->publishes([
            __DIR__ . '/../config/seat-assets.php' => config_path('seat-assets.php'),
        ], 'seat-assets-config');
    }

    /**
     * Register package services.
     */
    public function register(): void
    {
        // ── Merge Config ───────────────────────────────────────
        // Ensures defaults are always available even if user
        // hasn't published the config file.
        $this->mergeConfigFrom(
            __DIR__ . '/../config/seat-assets.php',
            'seat-assets'
        );

        // ── Bind Singletons ────────────────────────────────────
        $this->app->singleton(Services\UserResolverService::class);
        $this->app->singleton(Services\MarketPriceService::class);
        $this->app->singleton(Services\ReactionDataService::class);
        $this->app->singleton(Services\CostCalculatorService::class);
        $this->app->singleton(Services\ReactionCalculatorService::class);
        $this->app->singleton(Services\EveIndustryApiService::class);
        $this->app->singleton(Services\MarketSyncService::class);
        $this->app->singleton(Services\StockpileLogisticsService::class);
        $this->app->singleton(Services\BlueprintImportService::class);

        // ── Register SeAT Sidebar Menu ─────────────────────────
        $this->registerMenuEntries();
    }

    /**
     * Register sidebar menu items for the SeAT UI.
     */
    protected function registerMenuEntries(): void
    {
        if (file_exists(__DIR__ . '/Menu/package.sidebar.php')) {
            $this->mergeConfigFrom(
                __DIR__ . '/Menu/package.sidebar.php',
                'package.sidebar'
            );
        }
    }
}
