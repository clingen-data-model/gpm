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


    'affils' => [
        'base_url' => env('AFFILS_BASE_URL'),
        'api_key'  => env('AFFILS_API_KEY'),
        'timeout'  => 15,
        'paths'    => [
            'list'   => env('AFFILS_LIST_PATH',   '/api/affiliations_list/'),
            'create' => env('AFFILS_CREATE_PATH', '/api/affiliation/create/'),
            'detail' => env('AFFILS_DETAIL_PATH', '/api/affiliation_detail/'), // affiliation_detail/?affil_id={affiliation_id}            
            // 'update_affiliation' => env('AFFILS_UPDATE_BY_AFFID_PREFIX', '/api/affiliation/update/affiliation_id/'), // We don't use this, Affiliation ID on Affiliation Microservice (AM) is the one with format 10xxx
            'update_by_epid'  => env('AFFILS_UPDATE_BY_EPID_PREFIX', '/api/affiliation/update/expert_panel_id/'),
            'update_by_uuid'  => env('AFFILS_UPDATE_BY_UUID_PREFIX', '/api/affiliation/update/uuid/'),
        ],
        'endpoints' => [ // used in console commands app\Console\Commands\Dev\SyncGroupParentsFromAffils.php
            'cdwg_list'   => '/api/cdwg_list/',
            'cdwg_create' => '/api/cdwg/create/',
        ],
        'list_ttl' => (int) env('AFFILS_LIST_TTL', 900),
    ],

];
