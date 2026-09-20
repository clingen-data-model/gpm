<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Providers\IdpServiceProvider;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts routes to the fake identity-provider driver outside production.
 * Anywhere else the routes simply do not exist.
 */
class FakeIdpOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (IdpServiceProvider::driver() !== 'fake' || app()->isProduction()) {
            abort(404);
        }

        return $next($request);
    }
}
