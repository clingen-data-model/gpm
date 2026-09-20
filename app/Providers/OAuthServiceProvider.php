<?php

namespace App\Providers;

use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Passport\Http\Controllers\AccessTokenController;

/**
 * GPM as an OAuth2 server for machine-to-machine callers.
 *
 * Only the client-credentials grant is used: other systems hold a client id
 * and secret, exchange them at POST /oauth/token for a short-lived access
 * token, and send it as a bearer token to routes guarded by
 * App\Http\Middleware\AuthenticateSessionOrClient. Passport's other routes
 * (authorize, device, token management) are not registered.
 */
class OAuthServiceProvider extends ServiceProvider
{
    /**
     * Scopes a client may be granted. Keys are what callers request and what
     * routes require; values are human descriptions.
     */
    public const SCOPES = [
        'reports:read' => 'Read GPM reports and exports',
        'people:read' => 'Read people and their group memberships',
        'groups:read' => 'Read groups and expert panels',
    ];

    public function register(): void
    {
        Passport::ignoreRoutes();
    }

    public function boot(): void
    {
        Passport::tokensCan(self::SCOPES);
        Passport::tokensExpireIn(CarbonInterval::minutes(10));

        RateLimiter::for('oauth-token', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        Route::prefix('oauth')->as('passport.')->group(function () {
            Route::post('/token', [AccessTokenController::class, 'issueToken'])
                ->middleware('throttle:oauth-token')
                ->name('token');
        });
    }
}
