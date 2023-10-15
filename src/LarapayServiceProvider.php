<?php

namespace Susheelbhai\Larapay;

use Illuminate\Support\ServiceProvider;

class LarapayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'larapay');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->mergeConfigFrom(__DIR__.'/../config/larapay.php','larapay');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPublishable();
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Susheelbhai\Larapay\Commands\InitialSettings::class,
            ]);
        }
    }

    public function registerPublishable()
    {
        $this->publishes([
            __dir__ . "/Http/Controllers/LarapayController.php" => app_path('/Http/Controllers/LarapayController.php'),
        ], 'larapay');
    }
}
