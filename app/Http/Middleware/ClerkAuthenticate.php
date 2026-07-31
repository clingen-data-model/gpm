<?php

namespace App\Http\Middleware;

use Closure;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequest;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequestOptions;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClerkAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $psrRequest = new Psr7Request(
                $request->getMethod(),
                $request->fullUrl(),
                collect($request->headers->all())
                    ->map(fn ($value) => $value[0] ?? null)
                    ->filter()
                    ->all(),
                $request->getContent()
            );

            $options = new AuthenticateRequestOptions(
                secretKey: config('clerk.secret_key'),
                authorizedParties: config('clerk.authorized_parties'),
            );
            $requestState = AuthenticateRequest::authenticateRequest($psrRequest, $options);
            if (!$requestState->isAuthenticated()) { abort(401); }

            $claims = (array) $requestState->getPayload();
            $clerkUserId = $claims['sub'] ?? null;
            if (!$clerkUserId) { abort(401); }
            $request->attributes->set('clerk_user_id', $clerkUserId);
            $request->attributes->set('clerk_auth', $claims);
            return $next($request);
        } catch (\Throwable $e) {            
            return response()->json([
                'message' => 'Unable to authenticate Clerk request.',
            ], 401);
        }
    }
}