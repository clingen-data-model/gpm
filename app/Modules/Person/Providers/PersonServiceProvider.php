<?php

namespace App\Modules\Person\Providers;

use App\Modules\Foundation\ModuleServiceProvider;
use App\Modules\Person\Actions\InviteSendNotification;
use App\Modules\Person\Events\PersonInvited;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Policies\PersonPolicy;
use Illuminate\Support\Facades\Event;

class PersonServiceProvider extends ModuleServiceProvider
{
    protected $policies = [
        Person::class => PersonPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        parent::register();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();
        $this->registerPolicies();
        Event::listen(PersonInvited::class, [InviteSendNotification::class, 'listen']);
    }

    protected function getModulePath()
    {
        return __DIR__.'/..';
    }
}
