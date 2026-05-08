<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clerk Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key from the Clerk Dashboard API Keys page. Used to verify
    | session tokens via the Clerk JWKS endpoint and to call the Backend API
    | for user management (provisioning, import, etc.).
    |
    */

    'secret_key' => env('CLERK_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Clerk Publishable Key
    |--------------------------------------------------------------------------
    |
    | The publishable key from the Clerk Dashboard. Used only for reference
    | here; the Vue frontend reads it via VITE_CLERK_PUBLISHABLE_KEY.
    |
    */

    'publishable_key' => env('CLERK_PUBLISHABLE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Authorized Parties
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of origins permitted to generate session tokens for
    | this application. Verified against the `azp` JWT claim to prevent token
    | replay from other applications. Include all frontend origins.
    |
    */

    'authorized_parties' => array_filter(
        explode(',', env('CLERK_AUTHORIZED_PARTIES', ''))
    ),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The signing secret for incoming Clerk webhooks (from the Clerk Dashboard
    | Webhooks section). Used to verify Svix signatures.
    |
    */

    'webhook_secret' => env('CLERK_WEBHOOK_SECRET'),

];
