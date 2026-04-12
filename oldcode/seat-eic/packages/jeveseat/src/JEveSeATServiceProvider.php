<?php

namespace Apokavkos\JEveSeAT;

use Seat\Services\AbstractSeatPlugin;
use Apokavkos\JEveSeAT\Console\SyncTokens;

class JEveSeATServiceProvider extends AbstractSeatPlugin
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'jeveseat');

        $this->publishes([
            __DIR__ . '/Config/package.sidebar.php' => config_path('package.sidebar.jeveseat.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncTokens::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/package.sidebar.php', 'package.sidebar');
        $this->registerPermissions(__DIR__ . '/Config/Permissions/jeveseat.permissions.php', 'jeveseat');
    }

    public function getName(): string
    {
        return "jEveSeAT";
    }

    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/Apokavkos/jEveSeAT';
    }

    public function getPackagistPackageName(): string
    {
        return 'apokavkos/jeveseat';
    }

    public function getPackagistVendorName(): string
    {
        return 'apokavkos';
    }
}
