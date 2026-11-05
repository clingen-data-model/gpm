<?php

namespace App\Auth\CILogon;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

/**
 * Claims come from the userinfo endpoint using an access token obtained over the
 * back channel, so the id_token signature does not need to be verified here.
 */
class CILogonProvider extends AbstractProvider
{
    protected $scopes = ['openid', 'email', 'profile', 'org.cilogon.userinfo'];

    protected $scopeSeparator = ' ';

    protected $usesPKCE = true;

    public static function issuer(): string
    {
        return rtrim(config('services.cilogon.base_url'), '/');
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(static::issuer().'/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return static::issuer().'/oauth2/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(static::issuer().'/oauth2/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'] ?? null,
            'name' => $user['name'] ?? trim(($user['given_name'] ?? '').' '.($user['family_name'] ?? '')),
            'email' => $user['email'] ?? null,
        ]);
    }
}
