<?php

namespace GradziAu\Proda;

use GradziAu\Proda\Commands\ExpiringDevicesNotificationCommand;
use GradziAu\Proda\Commands\RefreshDevicesCommand;
use Illuminate\Support\ServiceProvider;

class ProdaServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind('proda', function ($app) {
            return new Client();
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/proda.php', 'proda');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/proda.php' => config_path('proda.php'),
            ], 'config');

            $this->commands([
                RefreshDevicesCommand::class,
                ExpiringDevicesNotificationCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}