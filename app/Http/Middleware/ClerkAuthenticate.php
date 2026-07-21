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

            $claims = $this->decodeJwtPayload($request->bearerToken());

            \Log::debug('Clerk token debug', [
                'sub' => data_get($claims, 'sub'),
                'iss' => data_get($claims, 'iss'),
                'azp' => data_get($claims, 'azp'),
                'aud' => data_get($claims, 'aud'),
                'exp' => data_get($claims, 'exp'),
                'authorized_parties' => config('clerk.authorized_parties'),
            ]);

            if (!$requestState->isSignedIn()) {
                return response()->json([
                    'message' => 'Unauthorized.',
                ], 401);
            }

            $bearerToken = $request->bearerToken();
            $claims = $this->decodeJwtPayload($bearerToken);
            $clerkUserId = data_get($claims, 'sub');

            if (!$clerkUserId) {
                return response()->json([
                    'message' => 'Authenticated Clerk token is missing sub claim.',
                ], 401);
            }

            $request->attributes->set('clerk_auth', $claims);
            $request->attributes->set('clerk_user_id', $clerkUserId);

            return $next($request);
        } catch (\Throwable $e) {
            \Log::error('Clerk auth failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'url' => $request->fullUrl(),
                'method' => $request->getMethod(),
                'has_authorization_header' => $request->hasHeader('Authorization'),
                'authorization_header_prefix' => substr((string) $request->header('Authorization'), 0, 20),
                'origin' => $request->header('Origin'),
                'referer' => $request->header('Referer'),
            ]);

            return response()->json([
                'message' => 'Unable to authenticate Clerk request.',
            ], 401);
        }
    }

    protected function decodeJwtPayload(?string $jwt): array
    {
        if (!$jwt) {
            return [];
        }

        $parts = explode('.', $jwt);

        if (count($parts) < 2) {
            return [];
        }

        $payload = $parts[1];
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode(strtr($payload, '-_', '+/'));

        return $decoded ? (json_decode($decoded, true) ?: []) : [];
    }
}