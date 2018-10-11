<?php

namespace Gmlo\Keycloak\Services;

use GuzzleHttp\Client;
use Gmlo\Keycloak\Auth\User;

class Keycloak
{
    protected $realm;
    protected $client_id;
    protected $client_secret;
    protected $base_uri;

    protected function setDefaultConection()
    {
        $this->realm = config('keycloak.realm');
        $this->client_id = config('keycloak.client_id');
        $this->client_secret = config('keycloak.client_secret');
        $this->base_uri = "realms/{$this->realm}/protocol/openid-connect/";
    }

    public function __construct()
    {
        $this->server_url = config('keycloak.server');
        $this->setDefaultConection();
    }

    public function getUserByToken($token)
    {
        return new User($token);
    }

    public function logout($user)
    {
        $response = $this->client()->post('logout', [
            'headers' => [
                'Authorization' => 'Bearer ' . $user->token,
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'client_id' => $this->client_id,
                'refresh_token' => $user->refresh_token,
                'client_secret' => $this->client_secret,
            ],
            'verify' => false,
        ]);
    }

    public function getUserByRefreshToken($refresh_token)
    {
        $response = $this->client()->post('token', [
            'form_params' => [
                'client_id' => $this->client_id,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
                'client_secret' => $this->client_secret,
            ],
            'verify' => false,
        ]);

        if ($response->getStatusCode() == 200) {
            $response = json_decode($response->getBody()->getContents());
            return new User($response->access_token, $response->refresh_token);
        }

        return false;
    }

    public function getToken($username, $password)
    {
        $response = $this->client()->post('token', [
            'form_params' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'username' => $username,
                'password' => $password,
                'grant_type' => 'password',
            ],
            'verify' => false,
        ]);

        if ($response->getStatusCode() == 200) {
            $response = json_decode($response->getBody()->getContents());
            return new User($response->access_token, $response->refresh_token);
        }

        return null;
    }

    protected function client()
    {
        return $this->client = new Client([
            'base_uri' => $this->server_url . $this->base_uri,
            'defaults' => [
                'exceptions' => false
            ],
            'http_errors' => false
        ]);
    }
}
