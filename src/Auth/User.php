<?php

namespace Gmlo\Keycloak\Auth;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;

class User extends Authenticatable
{
    protected $attributes = [];
    public $token;
    public $refresh_token;
    protected $casts = ['id' => 'string'];
    /*protected $attributes_keys = [
        'user', 'password', 'name', 'email', 'username', 'id', 'first_name', 'last_name', 'enabled', 'created_at', 'notes', 'phone_number'
    ];*/

    protected $default_attrs;
    protected $custom_attrs;

    public function __construct($info, $refresh_token = null)
    {
        $this->custom_attrs = config('keycloak.custom_attributes');
        $this->default_attrs = ['username', 'email', 'first_name', 'last_name', 'id', 'password', 'exp'];
        if (is_string($info)) {
            $this->setToken($info);
            $this->refresh_token = $refresh_token;
        } elseif (is_array($info)) {
            $this->fill($info);
        }
    }

    public function setToken($token)
    {
        $this->token = $token;
        $data = (new \Lcobucci\JWT\Parser())->parse((string)$token);
        foreach ($data->getClaims() as $key => $item) {
            $this->attributes[$key] = $data->getClaim($key);
        }
        $this->attributes['id'] = $this->attributes['sub'];
        $this->attributes['username'] = $this->attributes['preferred_username'];
        //dd($this->attributes);
    }

    public function encode()
    {
        $result = [
            'attributes' => []
        ];
        foreach ($this->attributes as $key => $value) {
            $attr = camel_case($key);

            if (in_array($key, ['attributes', 'password'])) {
                continue;
            }
            if (in_array($key, $this->default_attrs)) {
                $result[$attr] = $value;
            } elseif (in_array($key, $this->custom_attrs)) {
                $result['attributes'][$key] = [$value];
            }
        }

        if (isset($this->attributes['password']) and !empty($this->attributes['password'])) {
            $result['credentials'] = [[
                'type' => 'password',
                'value' => $this->attributes['password'],
                'temporary' => false,
            ]];
        }

        $result['enabled'] = config('keycloak.enabled_default');
        if (isset($this->attributes[config('keycloak.username_field')]) and !isset($this->attributes['username'])) {
            $result['username'] = $this->attributes[config('keycloak.username_field')];
        }

        return $result;
    }

    public function isActiveToken()
    {
        return !Carbon::parse(gmdate("Y-m-d\TH:i:s\Z", $this->attributes['exp']))->isPast();
    }

    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        if (method_exists($this, 'get' . ucfirst($key))) {
            return $this->{'get' . ucfirst($key)}();
        }
        //throw new Exception('' . $key . '" does not exist');
    }

    public function getAuthIdentifier()
    {
        $this->attributes['email'];
    }

    // ToDo
    /*public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }*/

    /*public function hasRole($role)
    {
        if (!isset($this->attributes['resource_access']->{$this->client_id})) {
            return false;
        }
        $resource = $this->attributes['resource_access']->{$this->client_id};
        return in_array($role, $resource->roles);
    }*/

    public function fill($data)
    {
        foreach ($data as $key => $value) {
            $attr = snake_case($key);

            if (in_array($attr, $this->default_attrs) or in_array($key, $this->custom_attrs)) {
                if (!is_array($value) || count($value) > 1) {
                    $this->attributes[$attr] = $value;
                } else {
                    $this->attributes[$attr] = $value[0];
                }
            }
        }

        if (isset($data['attributes'])) {
            $this->fill($data['attributes']);
        }

        if (isset($data['createdTimestamp'])) {
            // ToDo: check why doesn't work correctly (Other date);
            $this->attributes['created_at'] = Carbon::parse(gmdate("Y-m-d\TH:i:s\Z", $data['createdTimestamp']));
        }
    }

    public function hasTeams()
    {
        // ToDo
        return \App\Models\User::first()->hasTeams();
    }

    public function permissions()
    {
        // ToDo
        return \App\Models\User::first()->permissions();
    }

    public function getChannel()
    {
        // ToDo
        return \App\Models\User::first()->channel;
    }

    public function __toString()
    {
        return json_encode($this->attributes);
    }
}
