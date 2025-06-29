<?php 


namespace App\Services\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class AccessTokenManager
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $tokenUrl;
    protected string $cacheKey;

    public function __construct(array $config)
    {
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->tokenUrl = $config['oauth_url'];
        $this->cacheKey = $config['cache_key'] ?? md5($this->tokenUrl . $this->clientId);
    }

    public function getToken(): string
    {
        return Cache::remember($this->cacheKey, 150, function () {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => '',
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to retrieve access token: ' . $response->body());
            }

            $data = $response->json();
            Cache::put($this->cacheKey, $data['access_token'], now()->addSeconds($data['expires_in'] - 10));
            return $data['access_token'];
        });
    }
}
