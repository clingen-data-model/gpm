<?php

namespace App\Modules\Person\Providers;

use App\Listeners\RecordEvent;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\Group\Events\MemberInvited;
use App\Modules\Person\Events\PersonInvited;
use App\Modules\Person\Policies\PersonPolicy;
use App\Modules\Foundation\ModuleServiceProvider;
use App\Modules\Person\Actions\InviteSendNotification;

class PersonServiceProvider extends ModuleServiceProvider
{
    protected $policies = [
        Person::class => PersonPolicy::class,
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
        Event::listen(PersonInvited::class, [InviteSendNotification::class, 'listen']);
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
