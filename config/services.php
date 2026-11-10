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

    'publication' => [
        'base_url' => env('PUBLICATION_BASE_URL', 'https://www.ebi.ac.uk/europepmc/webservices/rest/search')
    ],

    'gt_api' => [
        'client_id'     => env('GT_CLIENT_API_ID'),
        'client_secret' => env('GT_CLIENT_API_SECRET'),
        'oauth_url'     => env('GT_CLIENT_BASE_URL') . '/oauth/token',
        'base_url'      => env('GT_CLIENT_BASE_URL') . env('GT_CLIENT_API'),
    ],

    'cilogon' => [
        'client_id' => env('CILOGON_CLIENT_ID'),
        'client_secret' => env('CILOGON_CLIENT_SECRET'),
        'redirect' => env('CILOGON_REDIRECT_URI', '/auth/cilogon/callback'),
        'base_url' => env('CILOGON_BASE_URL', 'https://cilogon.org'),
        // IdP entityIDs (the `idp` claim) whose asserted email may link an existing account.
        'email_trusted_idps' => array_filter(explode(',', env('CILOGON_EMAIL_TRUSTED_IDPS', ''))),
        // EntityIDs as listed at https://cilogon.org/idplist/ (not the OIDC issuers in CILogon's docs).
        'common_idps' => [
            ['id' => 'http://orcid.org/oauth/authorize', 'name' => 'ORCID'],
            ['id' => 'http://google.com/accounts/o8/id', 'name' => 'Google'],
            ['id' => 'http://login.microsoftonline.com/common/oauth2/v2.0/authorize', 'name' => 'Microsoft'],
            ['id' => 'http://github.com/login/oauth/authorize', 'name' => 'GitHub'],
        ],
        'idp_list_url' => env('CILOGON_IDP_LIST_URL', 'https://cilogon.org/idplist/'),
    ],

    'affiliation_api' => [
        'base_url' => env('AFFILIATION_API_BASE_URL'),
        'client_secret' => env('AFFILIATION_API_CLIENT_SECRET'),
    ],

];
