<?php

namespace Apokavkos\SeatAssets;

use Seat\Services\AbstractSeatPlugin;

class SeatAssetsServiceProvider extends AbstractSeatPlugin
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'seat-assets');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->publishes([
            __DIR__ . '/Config/package.sidebar.php' => config_path('package.sidebar.assets.php'),
            __DIR__ . '/Config/Permissions/assets.permissions.php' => config_path('assets.permissions.php'),
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/package.sidebar.php', 'package.sidebar');
        $this->registerPermissions(__DIR__ . '/Config/Permissions/assets.permissions.php', 'assets');
    }

    public function getName(): string
    {
        return "Apokavkos Asset Manager";
    }

    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/apokavkos/seat-assets';
    }

    public function getPackagistPackageName(): string
    {
        return 'apokavkos/seat-assets';
    }

    public function getPackagistVendorName(): string
    {
        return 'apokavkos';
    }
}
