<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Clerk\ClerkTokenVerifier;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies a bare Clerk session token and stashes its claims on the request.
 *
 * Unlike the stateless-bearer-token design, this middleware guards only the
 * login-exchange endpoint (and anywhere else a fresh Clerk token needs to be
 * turned into a local identity) — everywhere else relies on the Laravel
 * session established once that exchange succeeds.
 */
class ClerkAuthenticate
{
    public function __construct(private ClerkTokenVerifier $verifier)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $claims = $token ? $this->verifier->verify($token) : null;

        if (! $claims || empty($claims['sub'])) {
            return response()->json(['message' => 'A valid Clerk session is required.'], 401);
        }

        $request->attributes->set('clerk_claims', $claims);
        $request->attributes->set('clerk_user_id', $claims['sub']);

        return $next($request);
    }
}
