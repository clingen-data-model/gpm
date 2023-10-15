<?php

namespace App\Providers;

use App\Services\HgncLookup;
use App\Services\HgncLookupInterface;
use App\Services\MondoLookup;
use App\Services\MondoLookupInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
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
