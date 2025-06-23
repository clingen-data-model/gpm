<?php

namespace App\Services\GtApi;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

class GtApiClient
{
    protected string $baseUrl;
    protected AccessTokenManager $tokenManager;

    public function __construct(AccessTokenManager $tokenManager)
    {
        $this->baseUrl = config('services.gt_api.base_url');
        $this->tokenManager = $tokenManager;
    }

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        $accessToken = $this->tokenManager->getToken();

        return Http::timeout(10)
            ->retry(2, 200)
            ->withToken($accessToken)
            ->acceptJson();
    }

    public function post(string $endpoint, array $payload = []): Response
    {
        try {
            return $this->request()
                ->post($this->baseUrl . $endpoint, $payload)
                ->throw();
        } catch (RequestException $e) {
            report($e);
            throw new \Exception('GT API request failed: ' . $e->getMessage());
        }
    }
}
