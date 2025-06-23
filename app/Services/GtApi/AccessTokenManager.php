<?php 


namespace App\Services\GtApi;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class AccessTokenManager
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $tokenUrl;

    public function __construct()
    {
        $this->clientId = config('services.gt_api.client_id');
        $this->clientSecret = config('services.gt_api.client_secret');
        $this->tokenUrl = config('services.gt_api.oauth_url');
    }

    public function getToken(): string
    {
        // Check cache first
        return Cache::remember('gt_api_access_token', 150, function () {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => '', 
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to retrieve access token from GT API: ' . $response->body());
            }

            $data = $response->json();
            // Cache for slightly less than expires_in (default 180s)
            Cache::put('gt_api_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 10));

            return $data['access_token'];
        });
    }
}
