<?php

namespace Gmlo\Keycloak\Auth;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;

class User extends Authenticatable
{
    protected $data;
    public $token;
    public $refresh_token;
    protected $client_id;
    protected $attributes = [
        'user', 'password', 'name', 'email', 'username', 'id'
    ];

    public function __construct($token, $refresh_token = null)
    {
        $this->setToken($token);
        $this->refresh_token = $refresh_token;
    }

    public function setToken($token)
    {
        $this->token = $token;
        $data = (new \Lcobucci\JWT\Parser())->parse((string)$token);
        foreach ($data->getClaims() as $key => $item) {
            $this->data[$key] = $data->getClaim($key);
        }
        $this->data['id'] = $this->data['sub'];
        $this->data['username'] = $this->data['preferred_username'];
    }

    public function isActiveToken()
    {
        return !Carbon::parse(gmdate("Y-m-d\TH:i:s\Z", $this->data['exp']))->isPast();
    }

    /*public function __construct($data, $token, $client_id)
    {
        foreach ($data->getClaims() as $key => $item) {
            $this->data[$key] = $data->getClaim($key);
        }
        $this->token = $token;
        $this->client_id = $client_id;
        $this->data['id'] = $this->data['sub'];
        $this->data['username'] = $this->data['preferred_username'];
    }*/

    public function __get($key)
    {
        if (isset($this->{$key})) {
            return $this->{$key};
        }
        if (in_array($key, $this->attributes) and isset($this->data[$key])) {
            return $this->data[$key];
        }
        //throw new Exception('' . $key . '" does not exist');
    }

    public function getAuthIdentifier()
    {
        $this->data['email'];
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    public function hasRole($role)
    {
        if (!isset($this->data['resource_access']->{$this->client_id})) {
            return false;
        }
        $resource = $this->data['resource_access']->{$this->client_id};
        return in_array($role, $resource->roles);
    }
}
