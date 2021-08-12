<?php

namespace App\Modules\ExpertPanel\Providers;

use App\Listeners\RecordEvent;
use App\Events\RecordableEvent;
use Illuminate\Support\Facades\Event;
use App\Modules\Foundation\ClassGetter;
use Illuminate\Support\ServiceProvider;
use App\Modules\Foundation\ModuleServiceProvider;

class ExpertPanelModuleServiceProvider extends ModuleServiceProvider
{
    protected $listeners = [
        // EventClass::class => [ListenerClass::class]
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
        $this->mergeConfigFrom(
            __DIR__.'/../config.php',
            'applications'
        );
    }

    protected function getRoutesPath()
    {
        return __DIR__.'/../routes';
    }

    protected function getEventPath()
    {
        return (__DIR__.'/../Events');
    }
}
