<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        // Cached local configuration overrides PHPUnit's database environment.
        // Refuse to boot before RefreshDatabase can reset a non-test database.
        if ($app->configurationIsCached()) {
            throw new \RuntimeException('Tests require uncached configuration. Set APP_CONFIG_CACHE to a nonexistent test-only path before running PHPUnit.');
        }

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
