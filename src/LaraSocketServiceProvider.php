<?php

namespace Bagherkeshmiri\LaraSocket;

use Bagherkeshmiri\LaraSocket\Console\Commands\LaraSocketServeCommand;
use Illuminate\Support\ServiceProvider;

class LaraSocketServiceProvider extends ServiceProvider
{
    public function register()
    {
        $configPath = __DIR__ . '/config/larasocket.php';
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'larasocket');
        }
    }

    public function boot()
    {
        $configPath = __DIR__ . '/config/larasocket.php';
        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path('larasocket.php'),
            ], 'config');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                LaraSocketServeCommand::class,
            ]);
        }
    }
}
