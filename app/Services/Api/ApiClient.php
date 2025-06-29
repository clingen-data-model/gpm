<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

class ApiClient
{
    protected string $baseUrl;
    protected AccessTokenManager $tokenManager;

    public function __construct(string $baseUrl, AccessTokenManager $tokenManager)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->tokenManager = $tokenManager;
    }

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(10)
            ->retry(2, 200)
            ->withToken($this->tokenManager->getToken())
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
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }
}
