<?php

namespace Gmlo\Keycloak\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\SupportsBasicAuth;

class KeycloakGuard implements StatefulGuard, SupportsBasicAuth
{
    use GuardHelpers;

    protected $request;
    //protected $keycloak;
    protected $inputKey = 'keycloak_token';
    protected $loggedOut = false;
    protected $user = null;

    public function __construct(UserProvider $provider, Request $request, $configuration)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    public function getName()
    {
        return 'login_keycloak_' . sha1(static::class);
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool   $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if (!is_null($user)) {
            $this->login($user, $remember);
            return true;
        }

        return false;
    }

    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(\Illuminate\Contracts\Auth\Authenticatable $user, $remember = false)
    {
        $this->updateSession($user);

        $this->setUser($user);
    }

    /**
     * Update the session with the given token and  refresh_token.
     *
     * @param  string  $id
     * @return void
     */
    protected function updateSession($user)
    {
        $this->request->session()->put($this->getName() . '_token', $user->token);
        $this->request->session()->put($this->getName() . '_refresh_token', $user->refresh_token);
        $this->request->session()->migrate(true);
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function setUser(\Illuminate\Contracts\Auth\Authenticatable $user)
    {
        $this->user = $user;

        $this->loggedOut = false;

        return $this;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if ($this->loggedOut) {
            return;
        }

        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.

        if (!is_null($this->user)) {
            if (!$this->user->isActiveToken()) {
                //dd('as');
                $this->refreshSession();
                //dd($this->user);
            }
            return $this->user;
        }

        $token = $this->getToken();

        if (!is_null($token)) {
            $this->user = $this->provider->retrieveById($token);
            if (!$this->user->isActiveToken()) {
                $this->refreshSession();
            }
        }

        if (!is_null($this->user)) {
            $this->user->refresh_token = $this->getRefresToken();
        }

        return $this->user;
    }

    protected function refreshSession()
    {
        $user = $this->provider->authRefresh($this->getRefresToken());

        if (!is_null($user)) {
            $this->login($user);
            return true;
        }
        $this->user = null;
    }

    /**
     * Get token stored on session.
     *
     * @return null|string
     */
    protected function getToken()
    {
        return $this->request->session()->get($this->getName() . '_token');
    }

    /**
     * Get refresh_token stored on session
     *
     * @return null|string
     */
    protected function getRefresToken()
    {
        return $this->request->session()->get($this->getName() . '_refresh_token');
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        if ($this->user) {
            return $this->user->id;
        }
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
    }

    public function logout()
    {
        $user = $this->user();
        $this->provider->logout($user);
        $this->clearUserDataFromStorage();
        if (isset($this->events)) {
            $this->events->dispatch(new Events\Logout($user));
        }

        // Once we have fired the logout event we will clear the users out of memory
        // so they are no longer available as the user is no longer considered as
        // being signed into this application and should not be available here.
        $this->user = null;

        $this->loggedOut = true;
    }

    /**
     * Remove the user data from the session and cookies.
     *
     * @return void
     */
    protected function clearUserDataFromStorage()
    {
        $this->request->session()->remove($this->getName() . '_token');
        $this->request->session()->remove($this->getName() . '_refresh_token');
    }

    public function once(array $credentials = [])
    {
    }

    public function loginUsingId($id, $remember = false)
    {
    }

    public function onceUsingId($id)
    {
    }

    public function viaRemember()
    {
    }

    public function basic($field = 'email', $extraConditions = [])
    {
    }

    public function onceBasic($field = 'email', $extraConditions = [])
    {
    }
}
