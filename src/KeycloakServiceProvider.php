<?php

namespace Gmlo\Keycloak;

use Illuminate\Support\ServiceProvider;
use Gmlo\Keycloak\Auth\KeycloakUserProvider;
use Gmlo\Keycloak\Auth\KeycloakGuard;

class KeycloakServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/config/keycloak.php';
        $this->mergeConfigFrom($configPath, 'keycloak');
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        \Auth::extend('keycloak', function ($app, $name, array $config) {
            $userProvider = app(KeycloakUserProvider::class);
            $request = app('request');
            return new KeycloakGuard($userProvider, $request, $config);
        });
    }
}
