<?php

namespace SocialiteProviders\Dexcom;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'DEXCOM';
    public const US_API_URL = 'https://api.dexcom.com';
    public const EU_API_URL = 'https://api.dexcom.eu';
    public const JP_API_URL = 'https://api.dexcom.jp';
    public const SANDBOX_API_URL = 'https://sandbox-api.dexcom.com';

    protected $scopes = ['offline_access'];

    public function getApiUrl(): string
    {
        $mode = strtolower($this->getConfig('mode', 'sandbox'));

        if ($mode === 'us') {
            return self::US_API_URL;
        } elseif ($mode === 'eu') {
            return self::EU_API_URL;
        } elseif ($mode === 'jp') {
            return self::JP_API_URL;
        } else {
            return self::SANDBOX_API_URL;
        }
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getApiUrl() . '/v2/oauth2/login', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getApiUrl() . '/v2/oauth2/token';
    }

    public function user(): User
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());
        $user = $this->getUserByToken(Arr::get($response, 'access_token'));

        return $this->userInstance($response, $user);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token): array
    {
        $request = $this->getHttpClient()->get($this->getApiUrl() . '/v3/users/self/devices', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        $response = json_decode((string)$request->getBody(), true);

        return ['id' => $response['userId']];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'id')
        ]);
    }
}
