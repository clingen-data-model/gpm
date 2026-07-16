<?php

namespace App\Providers;

use App\Models\Document;
use App\Policies\DocumentPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use App\Services\Clerk\ClerkApiClient;
use App\Services\Clerk\ClerkTokenVerifier;
use App\Services\Clerk\ImpersonationTokenService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Document::class => DocumentPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function register()
    {
        $this->app->singleton(ClerkTokenVerifier::class, fn () => ClerkTokenVerifier::fromConfig());
        $this->app->singleton(ImpersonationTokenService::class, fn () => ImpersonationTokenService::fromConfig());
        $this->app->singleton(ClerkApiClient::class, fn () => ClerkApiClient::fromConfig());
    }

    public function boot()
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return url('/reset-password?token='.$token);
        });
    }
}
