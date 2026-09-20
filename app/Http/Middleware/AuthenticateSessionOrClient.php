<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;

/**
 * Lets one route serve both the SPA and machine callers.
 *
 * A request that already carries an authenticated user through the Sanctum
 * guard (the SPA's cookie session, a developer's make:user-token bearer, or
 * Sanctum::actingAs in tests) passes as is. Anything else must present a
 * Passport client-credentials token holding every listed scope, e.g.
 * `auth.session-or-client:reports:read`.
 */
class AuthenticateSessionOrClient
{
    public function __construct(private EnsureClientIsResourceOwner $clientCredentials)
    {
    }

    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        // A Passport JWT never matches Sanctum's token lookup, so this is a
        // clean "no" for machine callers rather than a false positive.
        if (Auth::guard('sanctum')->check()) {
            Auth::shouldUse('sanctum');

            return $next($request);
        }

        return $this->clientCredentials->handle($request, $next, ...$scopes);
    }
}
