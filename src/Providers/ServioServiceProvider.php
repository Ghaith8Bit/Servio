<?php

namespace Mrclutch\Servio\Providers;

use Illuminate\Support\ServiceProvider;
use Mrclutch\Servio\Supports\ServiceProviderLoader;

class ServioServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {}

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(ServiceProviderLoader::class);
    }
}
