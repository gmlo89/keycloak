<?php

namespace Gmlo\Keycloak\Manager;

use Gmlo\Keycloak\Services\Keycloak;
use Gmlo\Keycloak\Auth\User;

class UserRepo
{
    protected $keycloak;

    public function __construct()
    {
        $this->keycloak = new Keycloak(\Auth::user()->token, true);
    }

    public function findOrFail($id)
    {
        $user = $this->keycloak->getUser($id);

        if (is_null($user)) {
            abort(404);
        }

        return new User($user);
    }

    public function create($data)
    {
        $user = new User($data);
        return $this->keycloak->createNewUser($user->encode());
    }

    public function update($user_id, $data)
    {
        $user = $this->findOrFail($user_id);
        $user->fill($data);
        return $this->keycloak->updateUser($user->id, $user->encode());
    }

    public function delete($user_id)
    {
        return $this->keycloak->deleteUser($user_id);
    }

    public function fetchPagination($q = '', $needPagination = false, $perPage = 15)
    {
        $users_ = $this->keycloak->getUsers($q);

        $users = collect([]);

        if (is_null($users_)) {
            return $users;
        }

        foreach ($users_ as $user_) {
            $users->push(new User($user_));
        }

        if (is_null($perPage)) {
            $perPage = 15;
        }

        if ($needPagination == false) {
            $needPagination = 1;
        }

        $last_page = 0;
        if ($users->count() > 0) {
            $last_page = ceil($users->count() / $perPage);
        }

        return [
            'data' => $users->forPage($needPagination, $perPage),
            'last_page' => $last_page,
            'current_page' => $needPagination,
            'total' => $users->count(),
        ];
    }
}
