<?php

namespace Mrclutch\Servio\Providers;

use Illuminate\Support\ServiceProvider;
use Mrclutch\Servio\Console\Commands\GenerateServio;
use Mrclutch\Servio\Supports\ServiceProviderLoader;

class ServioServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '../config/servio.php' => config_path('servio.php')
        ], 'config');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(ServiceProviderLoader::class);

        $this->commands([
            GenerateServio::class,
        ]);
    }
}
