<?php

namespace App\Auth;

use Illuminate\Http\Request;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Services\Clerk\ClerkTokenVerifier;
use App\Services\Clerk\ImpersonationTokenService;

/**
 * Resolves the authenticated local User for an API request.
 *
 * Registered via Auth::viaRequest('clerk', ...) and used wherever the API
 * previously used the Sanctum guard. It accepts either:
 *
 *   1. An app-issued impersonation token (X-Impersonate-Token header) — the
 *      request resolves to the impersonated user, and the impersonator id is
 *      stashed on the request so the User model can expose `is_impersonating`.
 *   2. A Clerk session token (Authorization: Bearer <jwt>) — verified against
 *      Clerk's JWKS, then mapped to a local User by `clerk_id`, falling back
 *      to a verified email match with lazy linking.
 *
 * Authorization (Spatie roles/permissions, policies) is unchanged — this only
 * establishes identity.
 */
class ClerkGuard
{
    public function __construct(
        private readonly ClerkTokenVerifier $verifier,
        private readonly ImpersonationTokenService $impersonation,
    ) {
    }

    public function __invoke(Request $request): ?Authenticatable
    {
        if ($impToken = $request->header('X-Impersonate-Token')) {
            return $this->resolveImpersonation($request, $impToken);
        }

        $bearer = $request->bearerToken();

        if (! $bearer) {
            return null;
        }

        $claims = $this->verifier->verify($bearer);

        if ($claims === null) {
            return null;
        }

        return $this->resolveFromClerkClaims($claims);
    }

    /**
     * Resolve the impersonated user from a valid impersonation token.
     */
    private function resolveImpersonation(Request $request, string $token): ?Authenticatable
    {
        $payload = $this->impersonation->verify($token);

        if ($payload === null) {
            return null;
        }

        $target = User::find($payload['sub']);

        if (! $target) {
            return null;
        }

        // Make the impersonator id available to User::is_impersonating /
        // ::impersonated_by without relying on a server session.
        $request->attributes->set('impersonator_id', $payload['imp_by']);

        return $target;
    }

    /**
     * Map verified Clerk claims to a local User (linking on first sight).
     */
    private function resolveFromClerkClaims(array $claims): ?Authenticatable
    {
        $clerkId = $claims['sub'] ?? null;

        if (! $clerkId) {
            return null;
        }

        if ($user = User::where('clerk_id', $clerkId)->first()) {
            return $user;
        }

        $email = $claims['email'] ?? $this->lookupEmailFromClerk($clerkId);

        if (! $email) {
            return null;
        }

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

        if ($user) {
            // Lazy-link: bind this local account to its Clerk identity the
            // first time it authenticates (covers stragglers / accounts created
            // during the transition window).
            $user->forceFill(['clerk_id' => $clerkId])->save();
        }

        return $user;
    }

    /**
     * Fall back to the Clerk Backend API to find the user's primary email when
     * the session token does not carry an `email` claim.
     */
    private function lookupEmailFromClerk(string $clerkId): ?string
    {
        $secret = config('clerk.secret_key');

        if (! $secret) {
            return null;
        }

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->get(rtrim((string) config('clerk.api_url'), '/').'/users/'.$clerkId);

            if (! $response->successful()) {
                return null;
            }

            $user = $response->json();
            $primaryId = $user['primary_email_address_id'] ?? null;

            foreach ($user['email_addresses'] ?? [] as $address) {
                if (($address['id'] ?? null) === $primaryId) {
                    return $address['email_address'] ?? null;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Clerk email lookup failed', ['clerk_id' => $clerkId, 'error' => $e->getMessage()]);
        }

        return null;
    }
}
