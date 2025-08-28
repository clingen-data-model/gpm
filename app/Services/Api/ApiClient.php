<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;

class ApiClient
{
    protected string $baseUrl;
    protected AccessTokenManager $tokenManager;

    public function __construct(string $baseUrl, AccessTokenManager $tokenManager)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->tokenManager = $tokenManager;
    }

    protected function request(array $opts = []): PendingRequest
    {
        $timeout         = Arr::get($opts, 'timeout', 10);
        $connectTimeout  = Arr::get($opts, 'connect_timeout', 10);
        $retryTimes      = Arr::get($opts, 'retry', 2);
        $retrySleepMs    = Arr::get($opts, 'retry_sleep', 200);
        $headers         = Arr::get($opts, 'headers', []);
        $options         = Arr::get($opts, 'options', []);

        $req = Http::baseUrl($this->baseUrl)
            ->timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->retry($retryTimes, $retrySleepMs)
            ->withToken($this->tokenManager->getToken())
            ->acceptJson();

        if (!empty($headers)) {
            $req = $req->withHeaders($headers);
        }
        if (!empty($options)) {
            $req = $req->withOptions($options);
        }

        return $req;
    }

    public function post(string $endpoint, array $payload = [], array $opts = []): Response
    {
        try {
            return $this->request($opts)
                ->post($endpoint, $payload)
                ->throw();
        } catch (RequestException $e) {
            report($e);
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }

    public function get(string $endpoint, array $params = [], array $opts = []): Response
    {
        try {
            return $this->request($opts)
                ->get($endpoint, $params)
                ->throw();
        } catch (RequestException $e) {
            report($e);
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }
}