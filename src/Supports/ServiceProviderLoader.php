<?php

namespace Mrclutch\Servio\Supports;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class ServiceProviderLoader extends ServiceProvider
{
    /**
     * Register all service providers from the Services directory.
     */
    public function register()
    {
        $servicesPath = app_path('Services');

        if (!File::exists($servicesPath)) {
            return;
        }

        $directories = File::directories($servicesPath);

        foreach ($directories as $directory) {
            $serviceName = basename($directory);
            $providersPath = $directory . '/Providers';

            if (File::exists($providersPath)) {
                $providerFiles = File::files($providersPath);

                foreach ($providerFiles as $providerFile) {
                    $providerClass = "App\\Services\\{$serviceName}\\Providers\\" . $providerFile->getFilenameWithoutExtension();

                    if (class_exists($providerClass)) {
                        $this->app->register($providerClass);
                    }
                }
            }
        }
    }
}
