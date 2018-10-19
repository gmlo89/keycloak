<?php

namespace Gmlo\Keycloak\Facades;

use Illuminate\Support\Facades\Facade;

class UserRepo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'userrepo';
    }
}
