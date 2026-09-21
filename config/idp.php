<?php

/*
|--------------------------------------------------------------------------
| External identity provider (IdP)
|--------------------------------------------------------------------------
|
| GPM can accept sign-ins from an external identity provider in addition to
| local email/password credentials. The SPA obtains a session token from the
| IdP and exchanges it once for a normal Laravel session; every request after
| that uses the session cookie exactly as a local login does.
|
| Drivers:
|   clerk  Verify Clerk session JWTs against the instance JWKS and talk to
|          the Clerk Backend API.
|   fake   Fully offline stand-in for local development and tests: tokens
|          are signed with APP_KEY and IdP users live in a JSON file (or in
|          memory when the store path is empty).
|   null   IdP sign-in disabled; only local credentials work.
|
*/

return [
    'driver' => env('IDP_DRIVER', 'null'),

    // Value stored in users.idp_provider. The fake driver mimics Clerk so a
    // database linked against Clerk keeps working offline.
    'provider_name' => env('IDP_PROVIDER_NAME', 'clerk'),

    // Refresh users.name / users.email from the IdP record at each session login.
    'sync_profile_on_login' => (bool) env('IDP_SYNC_PROFILE', true),

    // Create an IdP identity for users created locally (invite redemption, artisan).
    'mirror_new_users' => (bool) env('IDP_MIRROR_USERS', true),

    // Typeahead search of the IdP directory when adding group members. The
    // query must reach min_query_length before the IdP is asked; results are
    // cached server-side for cache_ttl seconds to spare the provider's rate limits.
    'directory_search' => [
        'min_query_length' => (int) env('IDP_SEARCH_MIN_LENGTH', 3),
        'limit' => (int) env('IDP_SEARCH_LIMIT', 10),
        'cache_ttl' => (int) env('IDP_SEARCH_CACHE_TTL', 30),
    ],

    'clerk' => [
        'publishable_key' => env('CLERK_PUBLISHABLE_KEY'),
        'secret_key' => env('CLERK_SECRET_KEY'),
        'api_url' => env('CLERK_API_URL', 'https://api.clerk.com/v1'),

        // Frontend API origin of the instance; the JWT issuer and JWKS host.
        // e.g. https://verb-noun-00.clerk.accounts.dev or https://clerk.example.org
        'frontend_api_url' => env('CLERK_FRONTEND_API_URL'),

        // Origins allowed in the token's azp claim. Comma separated. Defaults to APP_URL.
        'authorized_parties' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('CLERK_AUTHORIZED_PARTIES', env('APP_URL', '')))
        ))),

        'jwks_cache_ttl' => (int) env('CLERK_JWKS_CACHE_TTL', 3600),
        'clock_skew_leeway' => (int) env('CLERK_CLOCK_SKEW_LEEWAY', 60),
    ],

    'fake' => [
        // JSON file holding fake IdP users. Empty string => in-memory only (tests).
        'store' => env('IDP_FAKE_STORE', storage_path('app/fake-idp/users.json')),
        'token_ttl' => (int) env('IDP_FAKE_TOKEN_TTL', 300),
    ],
];
