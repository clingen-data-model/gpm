<?php

namespace App\Providers;

use App\Models\Document;
use App\Auth\ClerkGuard;
use App\Policies\DocumentPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Register Clerk authentication services.
     */
    public function register()
    {
        parent::register();

        $this->app->singleton(ClerkTokenVerifier::class, fn () => ClerkTokenVerifier::fromConfig());
        $this->app->singleton(ImpersonationTokenService::class, fn () => ImpersonationTokenService::fromConfig());
        $this->app->singleton(ClerkApiClient::class, fn () => ClerkApiClient::fromConfig());
    }

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function boot()
    {
        // Stateless API identity backed by Clerk session tokens (and app-issued
        // impersonation tokens). Used by the `clerk` guard in config/auth.php.
        Auth::viaRequest('clerk', function (Request $request) {
            return app(ClerkGuard::class)($request);
        });
    }
}
