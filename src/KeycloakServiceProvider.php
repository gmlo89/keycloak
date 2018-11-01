<?php

namespace Gmlo\Keycloak;

use Illuminate\Support\ServiceProvider;
use Gmlo\Keycloak\Auth\KeycloakUserProvider;
use Gmlo\Keycloak\Auth\KeycloakGuard;
use Gmlo\Keycloak\Manager\UserRepo;

//use Gmlo\Keycloak\Manager\UserRepo;

class KeycloakServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        /*App::bind('userrepo', function () {
            return new UserRepo();
        });*/
        $this->app->bind('userrepo', function ($app) {
            return new UserRepo();
        });

        $this->mergeConfigFrom(
            __DIR__ . '/config/keycloak.php',
            'keycloak'
        );
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        /*
        \Auth::extend('keycloak', function ($app, $name, array $config) {
            $userProvider = app(KeycloakUserProvider::class);
            $request = app('request');
            return new KeycloakGuard($userProvider, $request, $config);
        });*/

        $this->publishes([__DIR__ . '/config/keycloak.php' => config_path('keycloak.php')]);
    }

    public function provides()
    {
        return ['userrepo'];
    }
}
