<?php

namespace App\Modules\Person\Providers;

use App\Listeners\RecordEvent;
use App\Events\RecordableEvent;
use Illuminate\Support\Facades\Event;
use App\Modules\Foundation\ClassGetter;
use Illuminate\Support\ServiceProvider;
use App\Modules\Person\Events\PersonEvent;
use App\Modules\Person\Events\PersonDataUpdated;
use App\Modules\Foundation\ModuleServiceProvider;

class PersonServiceProvider extends ModuleServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    protected function getRoutesPath()
    {
        return __DIR__.'/../routes';
    }

    protected function getEventPath()
    {
        return __DIR__.'/../Events';
    }
}
