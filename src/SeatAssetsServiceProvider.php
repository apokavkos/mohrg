<?php

namespace Apokavkos\SeatAssets;

use Seat\Services\AbstractSeatPlugin;

class SeatAssetsServiceProvider extends AbstractSeatPlugin
{
    public function boot()
    {
        $this->add_routes();
        $this->add_views();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/package.sidebar.php', 'package.sidebar');
        $this->registerPermissions(__DIR__ . '/Config/seat-assets.permissions.php', 'seat-assets');
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

    private function add_routes()
    {
        if (!$this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        }
    }

    private function add_views()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'seat-assets');
    }
}
