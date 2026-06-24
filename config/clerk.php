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
    | When a Clerk session token carries an "azp" (authorized party) claim it
    | must match one of these origins. This guards against the token being
    | replayed from an unexpected origin. Leave empty to skip the check.
    |
    */

    'authorized_parties' => array_filter(
        explode(',', (string) env('CLERK_AUTHORIZED_PARTIES', env('APP_URL', '')))
    ),

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
