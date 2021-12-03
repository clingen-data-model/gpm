<?php

namespace App\Modules\Group\Providers;

use App\Listeners\RecordEvent;
use App\Events\RecordableEvent;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use App\Modules\Foundation\ClassGetter;
use Illuminate\Support\ServiceProvider;
use App\Modules\Group\Policies\GroupPolicy;
use App\Modules\Foundation\ModuleServiceProvider;

class GroupModuleServiceProvider extends ModuleServiceProvider
{
    protected $listeners = [
        // EventClass::class => [ListenerClass::class]
    ];

    protected $policies = [
        Group::class => GroupPolicy::class,
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
        $this->mergeConfigFrom(
            __DIR__.'/../groups.php',
            'groups'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../specifications.php',
            'specifications'
        );
    }

    protected function getModulePath()
    {
        return (__DIR__.'/..');
    }
}
