<?php

namespace App\Providers;

use GuzzleHttp\Client;
use App\Services\HgncLookup;
use App\Services\MondoLookup;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\URL;
use App\Services\HgncLookupInterface;
use App\Services\MondoLookupInterface;
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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(MondoLookupInterface::class, MondoLookup::class);
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
