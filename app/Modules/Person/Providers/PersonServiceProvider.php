<?php

namespace App\Modules\Person\Providers;

use App\Listeners\RecordEvent;
use App\Events\RecordableEvent;
use Illuminate\Support\Facades\Gate;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\Foundation\ClassGetter;
use Illuminate\Support\ServiceProvider;
use App\Modules\Person\Events\PersonEvent;
use App\Modules\Person\Policies\ProfilePolicy;
use App\Modules\Person\Events\PersonDataUpdated;
use App\Modules\Foundation\ModuleServiceProvider;

class PersonServiceProvider extends ModuleServiceProvider
{
    protected $policies = [
        Person::class => ProfilePolicy::class,
    ];

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
        $this->registerPolicies();
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
