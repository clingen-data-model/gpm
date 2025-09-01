<?php

namespace App\Providers;

use GuzzleHttp\Client;
use App\Services\HgncLookup;
use App\Services\DiseaseLookup;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\URL;
use App\Services\HgncLookupInterface;
use App\Services\DiseaseLookupInterface;
use Infrastructure\Service\MessageBus;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;
use Infrastructure\Service\MessageBusInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->isLocal() && config('telescope.enabled') && class_exists(\Laravel\Telescope\TelescopeApplicationService::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(\Laravel\Telescope\TelescopeApplicationServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(DiseaseLookupInterface::class, DiseaseLookup::class);
        $this->app->bind(HgncLookupInterface::class, HgncLookup::class);

        if (config('app.url_scheme')) {
            URL::forceScheme(config('app.url_scheme'));
        }
        Actions::registerCommands('app/Actions');

        $this->app->bind(ClientInterface::class, function () {
            return new Client();
        });
    }
}
