<?php

use Illuminate\Support\Facades\Facade;

return [
    'name' => env('APP_NAME', 'GPM'),
    'env' => env('APP_ENV', 'production'),
    'build' => [
        'name' => env('OPENSHIFT_BUILD_NAME', 'dev'),
        'commit' => env('OPENSHIFT_BUILD_COMMIT', null),
    ],
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL', null),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'url_scheme' => env('URL_SCHEME', null),
    'test_scheduler' => env('TEST_SCHEDULER', false),
    'providers' => [
        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        // Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */

        /*
        * Application Service Providers...
        */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BusServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\FortifyServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

        /**
         *  Module Providers
         */
        App\Modules\ExpertPanel\Providers\ExpertPanelModuleServiceProvider::class,
        App\Modules\Group\Providers\GroupModuleServiceProvider::class,
        App\Modules\Person\Providers\PersonServiceProvider::class,
        App\Modules\User\Providers\UserModuleServiceProvider::class,
        App\Tasks\Providers\TaskServiceProvider::class,

        App\DataExchange\KafkaServiceProvider::class,
        App\DataExchange\DataExchangeServiceProvider::class,

    ],
    'aliases' => Facade::defaultAliases()->merge([
        'Clockwork' => Clockwork\Support\Laravel\Facade::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
    ])->toArray(),
    'jira' => [
        'user' => env('JIRA_USER'),
        'token' => env('JIRA_API_TOKEN'),
    ],
    'features' => [
        'notify_scope_change' => env('FEATURE_NOTIFY_SCOPE_CHANGE', true),
        'specification_upload' => env('FEATURE_SPEC_UPLOAD', true),
        'cspec_summary' => env('FEATURE_CSPEC_SUMMARY', true),
        'chair_review' => env('FEATURE_CHAIR_REVIEW', true),
    ],
];
