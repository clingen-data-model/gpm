<?php

namespace App\Modules\Person\Providers;

use App\Listeners\RecordEvent;
use App\Events\RecordableEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use App\Modules\Person\Events\PersonEvent;
use App\Modules\Person\Events\PersonDataUpdated;

class PersonServiceProvider extends ServiceProvider
{
    protected $listeners = [
        PersonDataUpdated::class => [],
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->listeners as $event => $listeners) {
            if (is_subclass_of($event, RecordableEvent::class)) {
                Event::listen($event, [RecordEvent::class, 'handle']);
            }
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
