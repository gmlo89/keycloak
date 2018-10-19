<?php

namespace Gmlo\Keycloak\Services;

use GuzzleHttp\Client;

class Keycloak
{
    protected $realm;
    protected $client_id;
    protected $client_secret;
    protected $base_uri;
    protected $token;

    public function __construct($token = null, $admin = false)
    {
        $this->token = $token;
        $this->realm = config('keycloak.realm');
        $this->client_id = config('keycloak.client_id');
        $this->client_secret = config('keycloak.client_secret');
        $this->server_url = config('keycloak.server');

        if ($admin) {
            $this->base_uri = "admin/realms/{$this->realm}/";
        } else {
            $this->base_uri = "realms/{$this->realm}/protocol/openid-connect/";
        }
    }

    public function logout($user)
    {
        return $this->post('logout', $user->token, null, [
            'client_id' => $this->client_id,
            'refresh_token' => $user->refresh_token,
            'client_secret' => $this->client_secret,
        ]);
    }

    public function getUserByRefreshToken($refresh_token)
    {
        return $this->post('token', null, [
            'client_id' => $this->client_id,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
            'client_secret' => $this->client_secret,
        ]);
    }

    public function getToken($username, $password)
    {
        return $this->post('token', null, [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password',
        ]);
    }

    public function getUsers($query)
    {
        return $this->get('users', $this->token, ['search' => $query]);
    }

    public function getUser($user_id)
    {
        return $this->get("users/{$user_id}", $this->token);
    }

    public function deleteUser($user_id)
    {
        return $this->delete("users/{$user_id}", $this->token);
    }

    public function updateUser($user_id, $data)
    {
        return $this->put("users/{$user_id}", $this->token, null, $data);
    }

    public function createNewUser($data)
    {
        return $this->post('users', $this->token, null, $data);
    }

    protected function delete($path, $token = null, $form_params = null, $json = null)
    {
        return $this->request('delete', $path, $token, $form_params, $json);
    }

    protected function put($path, $token = null, $form_params = null, $json = null)
    {
        return $this->request('put', $path, $token, $form_params, $json);
    }

    protected function post($path, $token = null, $form_params = null, $json = null)
    {
        return $this->request('post', $path, $token, $form_params, $json);
    }

    protected function get($path, $token = null, $form_params = null, $json = null)
    {
        return $this->request('get', $path, $token, $form_params, $json);
    }

    protected function request($method, $path, $token = null, $form_params = null, $json = null)
    {
        $info = [
            'verify' => true,
            'headers' => [],
        ];
        if (!is_null($token)) {
            $info['headers']['Authorization'] = 'Bearer ' . $token;
        }
        if (!is_null($json)) {
            $info['json'] = $json;
        }
        if (!is_null($form_params)) {
            $info['form_params'] = $form_params;
        }

        info(json_encode($info));

        $response = $this->client()->{$method}($path, $info);
        info(json_encode($response));

        if (in_array($response->getStatusCode(), [200, 201])) {
            return  json_decode($response->getBody()->getContents(), true);
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
