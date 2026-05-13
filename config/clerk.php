<?php

return [
    'base_url' => env('CLERK_BASE_URL', 'https://api.clerk.com/v1'),
    'secret_key' => env('CLERK_SECRET_KEY'),
    'publishable_key' => env('CLERK_PUBLISHABLE_KEY'),
    'jwt_key' => env('CLERK_JWT_KEY'),
    'webhook_signing_secret' => env('CLERK_WEBHOOK_SIGNING_SECRET'),
    'invitation_redirect_url' => env('CLERK_INVITATION_REDIRECT_URL'),
    'authorized_parties' => array_values(array_filter(array_map('trim',explode(',', env('CLERK_AUTHORIZED_PARTIES', env('APP_URL', '')))))),
];