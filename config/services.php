<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gt_api' => [
        'client_id'     => env('GT_CLIENT_API_ID'),
        'client_secret' => env('GT_CLIENT_API_SECRET'),
        'oauth_url'     => env('GT_CLIENT_BASE_URL') . '/oauth/token',
        'base_url'      => env('GT_CLIENT_BASE_URL') . env('GT_CLIENT_API'),
    ],

    'affiliation_api' => [
        'base_url' => env('AFFILIATION_API_BASE_URL'),
        'client_id' => env('AFFILIATION_API_CLIENT_ID'),
        'client_secret' => env('AFFILIATION_API_CLIENT_SECRET'),
        'oauth_url' => env('AFFILIATION_API_OAUTH_URL'),
    ],
];
