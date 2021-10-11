<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Infrastructure\Service\MessageBus;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Service\MessageBusInterface;
use Lorisleiva\Actions\Facades\Actions;

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
        if (config('app.url_scheme')) {
            URL::forceScheme(config('app.url_scheme'));
        }
        Actions::registerCommands('app/Actions');
    }
}
