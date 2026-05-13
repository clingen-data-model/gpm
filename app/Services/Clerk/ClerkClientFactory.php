<?php

namespace App\Services\Clerk;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ClerkClientFactory
{
    public function make(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('clerk.base_url'), '/'))
            ->withToken(config('clerk.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }
}