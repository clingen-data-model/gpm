<?php

namespace App\Modules\User\Providers;

use App\Modules\Foundation\ModuleServiceProvider;
use Lorisleiva\Actions\Facades\Actions;

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
    public function register(): void
    {
        parent::register();
    }

    public function boot()
    {
        parent::boot();
        // Actions::registerCommands(__DIR__.'/../Actions');
    }

    protected function getModulePath()
    {
        return __DIR__.'/..';
    }
}
