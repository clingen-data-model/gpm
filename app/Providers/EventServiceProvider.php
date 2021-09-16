<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use App\Listeners\CreateUserFromInvite;

use App\Modules\User\Events\UserLoggedIn;
use App\Modules\User\Events\UserLoggedOut;
use App\Modules\Person\Events\InviteRedeemed;
use App\Modules\User\Events\UserAuthenticated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // NOTE That intra-module listeners are registered in the module's service provider.
        'Illuminate\Mail\Events\MessageSent' => [
            'App\Listeners\Mail\StoreMailInDatabase'
        ],
        InviteRedeemed::class => [
            CreateUserFromInvite::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(function (Login $event) {
            Event::dispatch(new UserLoggedIn($event->user));
        });
        Event::listen(function (Logout $event) {
            Event::dispatch(new UserLoggedOut($event->user));
        });
    }
}
