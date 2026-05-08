<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\User\Services\ClerkUserResolver;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequest;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequestOptions;

class VerifyClerkToken
{
    public function __construct(private ClerkUserResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only intercept requests that carry a Bearer token.
        // Requests without one fall through to the next middleware (e.g. Sanctum)
        // so both auth paths can coexist during the transition period.
        if (! $request->bearerToken()) {
            return $next($request);
        }

        $options = new AuthenticateRequestOptions(
            secretKey: config('clerk.secret_key'),
            authorizedParties: config('clerk.authorized_parties'),
        );

        $state = AuthenticateRequest::authenticateRequest($request, $options);

        if (! $state->isAuthenticated()) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $clerkUserId = $state->getPayload()->sub ?? null;

        if (! $clerkUserId) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->resolver->resolve($clerkUserId);

        if (! $user) {
            // Valid Clerk identity, but not provisioned or active in this application.
            return response()->json(['message' => 'Access to this application has not been granted.'], Response::HTTP_FORBIDDEN);
        }

        Auth::setUser($user);

        return $next($request);
    }
}
