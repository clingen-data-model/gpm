<?php

namespace App\Modules\User\Providers;

use App\Listeners\RecordEvent;
use App\Events\RecordableEvent;
use Illuminate\Support\Facades\Event;
use App\Modules\Foundation\ClassGetter;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;
use Illuminate\Auth\Events\Authenticated;
use App\Modules\Foundation\ModuleServiceProvider;

class UserModuleServiceProvider extends ModuleServiceProvider
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

    public function boot()
    {
        Actions::registerCommands(__DIR__.'/../Actions');
    }
    

    protected function getEventPath()
    {
        return (__DIR__.'/../Events');
    }
}
