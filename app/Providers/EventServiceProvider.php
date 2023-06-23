<?php

namespace App\Providers;

use App\Actions\CommentNotifyAboutEvent;
use App\Events\CommentCreated;
use App\Events\CommentDeleted;
use App\Events\CommentResolved;
use App\Events\CommentUpdated;
use App\Modules\Group\Actions\JudgementNotifyAboutEvent;
use App\Modules\Group\Events\JudgementCreated;
use App\Modules\Group\Events\JudgementDeleted;
use App\Modules\Group\Events\JudgementUpdated;
use App\Modules\User\Events\UserLoggedIn;
use App\Modules\User\Events\UserLoggedOut;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    use RegistersExplicitEventListeners;

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // NOTE That intra-module listeners are registered in the module's service provider.
        'Illuminate\Mail\Events\MessageSent' => [
            \App\Listeners\Mail\StoreMailInDatabase::class,
        ],
        CommentCreated::class => [CommentNotifyAboutEvent::class],
        CommentUpdated::class => [CommentNotifyAboutEvent::class],
        CommentDeleted::class => [CommentNotifyAboutEvent::class],
        CommentResolved::class => [CommentNotifyAboutEvent::class],

        JudgementCreated::class => [JudgementNotifyAboutEvent::class],
        JudgementUpdated::class => [JudgementNotifyAboutEvent::class],
        JudgementDeleted::class => [JudgementNotifyAboutEvent::class],
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

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}

