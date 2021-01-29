<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Infrastructure\Service\MessageBus;
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
    }
}
