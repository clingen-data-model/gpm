<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Clerk\ImpersonationTokenService;

/**
 * Swaps the session-authenticated user for an impersonation target.
 *
 * Runs after the session (`auth:sanctum`) guard has already resolved the real
 * admin. Impersonation itself stays stateless (an app-signed JWT the SPA
 * sends as X-Impersonate-Token) rather than session-based — it doesn't need
 * to change shape just because the primary identity check moved from
 * per-request bearer verification to a login-time session.
 */
class ApplyImpersonationToken
{
    public function __construct(private ImpersonationTokenService $impersonation)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Impersonate-Token');

        if ($token) {
            $payload = $this->impersonation->verify($token);

            if ($payload && ($target = User::find($payload['sub']))) {
                $request->attributes->set('impersonator_id', $payload['imp_by']);
                Auth::setUser($target);
            }
        }

        return $next($request);
    }
}
