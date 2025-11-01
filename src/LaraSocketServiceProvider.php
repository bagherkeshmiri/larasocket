<?php

namespace Bagherkeshmiri\LaraSocket;

use Illuminate\Support\ServiceProvider;

class LaraSocketServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Load the package config
        $this->mergeConfigFrom(__DIR__ . '/config/larasocket.php', 'larasocket');
    }

    public function boot()
    {
        // Publish the config file to the application
        $this->publishes([
            __DIR__ . '/config/larasocket.php' => config_path('larasocket.php'),
        ], 'config');

        // Register artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\LaraSocketServe::class,
            ]);
        }
    }
}
