<?php

namespace Gmlo\Keycloak\Auth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Gmlo\Keycloak\Services\Keycloak;

class KeycloakUserProvider implements UserProvider
{
    protected $keycloak;

    public function __construct()
    {
        $this->keycloak = new Keycloak();
    }

    /**
     * Check if credentials are correct and retrieve a token and refresh_token
     *
     * @param array $credentials
     * @return void
     */
    public function retrieveByCredentials(array $credentials)
    {
        return $this->keycloak->getToken($credentials['username'], $credentials['password']);
    }

    public function retrieveById($identifier)
    {
        return $this->keycloak->getUserByToken($identifier);
    }

    /**
     * Validate user and get with refresh_token
     *
     * @param [type] $refresh_token
     * @return void
     */
    public function authRefresh($refresh_token)
    {
        return $this->keycloak->getUserByRefreshToken($refresh_token);
    }

    public function retrieveByToken($identifier, $token)
    {
    }

    //-----------------------------------------------------------

    public function auth($user, $password)
    {
    }

    public function logout($user)
    {
        $this->keycloak->logout($user);
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
    }
}
