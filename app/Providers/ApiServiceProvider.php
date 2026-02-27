<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Api\AccessTokenManager;
use App\Services\Api\ApiClient;
use App\Services\Api\GtApiService;
use Illuminate\Support\Facades\Log;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(GtApiService::class, function () {
            $config = config('services.gt_api');

            $tokenManager = new AccessTokenManager([
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'oauth_url' => $config['oauth_url'],
                'cache_key' => 'gt_api_access_token',
            ]);

            // Create ApiClient on the fly for this service
            $apiClient = new ApiClient($config['base_url'], $tokenManager);

            return new GtApiService($apiClient);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}