<?php

namespace App\Providers;

use InvalidArgumentException;
use Illuminate\Support\ServiceProvider;
use App\Services\Idp\Fake\FakeIdpStore;
use App\Services\Idp\Clerk\ClerkClient;
use App\Services\Idp\Fake\FakeIdpClient;
use App\Services\Idp\Fake\FakeTokenIssuer;
use App\Services\Idp\Contracts\IdpClient;
use App\Services\Idp\Fake\FakeTokenVerifier;
use App\Services\Idp\Clerk\ClerkTokenVerifier;
use App\Services\Idp\NullDriver\NullIdpClient;
use App\Services\Idp\Contracts\TokenVerifier;
use App\Services\Idp\NullDriver\NullTokenVerifier;

/**
 * Binds the identity-provider contracts to the driver selected in config/idp.php.
 */
class IdpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/idp.php', 'idp');

        $this->app->singleton(FakeIdpStore::class, function () {
            $path = config('idp.fake.store');

            return new FakeIdpStore($path ? (string) $path : null);
        });
        $this->app->singleton(FakeIdpClient::class);
        $this->app->singleton(FakeTokenIssuer::class, fn () => FakeTokenIssuer::fromConfig());
        $this->app->singleton(FakeTokenVerifier::class, fn () => FakeTokenVerifier::fromConfig());

        $this->app->singleton(TokenVerifier::class, fn ($app) => $this->makeTokenVerifier($app));
        $this->app->singleton(IdpClient::class, fn ($app) => $this->makeClient($app));
    }

    public static function driver(): string
    {
        return (string) config('idp.driver', 'null');
    }

    public static function enabled(): bool
    {
        return self::driver() !== 'null';
    }

    protected function makeTokenVerifier($app): TokenVerifier
    {
        return match (self::driver()) {
            'clerk' => ClerkTokenVerifier::fromConfig(),
            'fake' => $app->make(FakeTokenVerifier::class),
            'null' => new NullTokenVerifier(),
            default => throw new InvalidArgumentException('Unsupported IdP driver: '.self::driver()),
        };
    }

    protected function makeClient($app): IdpClient
    {
        return match (self::driver()) {
            'clerk' => ClerkClient::fromConfig(),
            'fake' => $app->make(FakeIdpClient::class),
            'null' => new NullIdpClient(),
            default => throw new InvalidArgumentException('Unsupported IdP driver: '.self::driver()),
        };
    }
}
