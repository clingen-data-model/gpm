<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clerk API Keys
    |--------------------------------------------------------------------------
    |
    | The secret key is used for server-to-server Clerk Backend API calls
    | (e.g. importing users). The publishable key identifies the frontend
    | instance and is also surfaced to the SPA via VITE_CLERK_PUBLISHABLE_KEY.
    |
    */

    'secret_key' => env('CLERK_SECRET_KEY'),

    'publishable_key' => env('CLERK_PUBLISHABLE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Clerk API Endpoints
    |--------------------------------------------------------------------------
    |
    | "api_url" is the Backend API base (used for user import/lookup).
    | "frontend_api_url" is your instance's Frontend API origin; its JWKS
    | endpoint (used to verify session tokens) is derived by appending
    | "/.well-known/jwks.json".
    |
    */

    'api_url' => env('CLERK_API_URL', 'https://api.clerk.com/v1'),

    'frontend_api_url' => env('CLERK_FRONTEND_API_URL'),

    /*
    |--------------------------------------------------------------------------
    | Authorized Parties
    |--------------------------------------------------------------------------
    |
    | A Clerk session token's "azp" (authorized party) claim must match one of
    | these origins. This guards against a token issued to another application
    | on the same Clerk instance being replayed here. When this list is set, a
    | token with no azp claim is rejected; leave it empty to skip the check
    | entirely.
    |
    */

    'authorized_parties' => array_filter(
        explode(',', (string) env('CLERK_AUTHORIZED_PARTIES', env('APP_URL', '')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Clock Skew Leeway (seconds)
    |--------------------------------------------------------------------------
    |
    | Tolerance applied to the exp/nbf/iat claims, absorbing clock drift
    | between this host and Clerk. Without it, a few seconds of drift surfaces
    | as spurious 401s.
    |
    */

    'clock_skew_leeway' => (int) env('CLERK_CLOCK_SKEW_LEEWAY', 60),

    /*
    |--------------------------------------------------------------------------
    | JWKS Cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | How long the fetched JWKS signing keys are cached. Verification is
    | networkless within this window. Clerk rotates keys rarely, so an hour
    | is a safe default.
    |
    */

    'jwks_cache_ttl' => (int) env('CLERK_JWKS_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Impersonation Token TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Lifetime of the short-lived, app-signed impersonation token issued when
    | an admin impersonates another user. Kept short since it bypasses Clerk.
    |
    */

    'impersonation_ttl' => (int) env('CLERK_IMPERSONATION_TTL', 1800),

];
