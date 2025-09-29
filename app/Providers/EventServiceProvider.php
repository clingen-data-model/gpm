<?php

namespace App\Providers;

use App\Events\CommentCreated;
use App\Events\CommentDeleted;
use App\Events\CommentUpdated;
use App\Events\CommentResolved;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use App\Actions\CommentNotifyAboutEvent;
use App\Modules\User\Events\UserLoggedIn;
use App\Modules\User\Events\UserLoggedOut;
use App\Modules\Group\Events\JudgementCreated;
use App\Modules\Group\Events\JudgementDeleted;
use App\Modules\Group\Events\JudgementUpdated;
use App\Modules\Group\Actions\JudgementNotifyAboutEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\ExpertPanel\Listeners\SendGenesToGtOnGcepApproval;
use App\Modules\ExpertPanel\Listeners\RegisterMembersInGtOnGcepApproval;

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
            'App\Listeners\Mail\StoreMailInDatabase'
        ],
        CommentCreated::class => [CommentNotifyAboutEvent::class],
        CommentUpdated::class => [CommentNotifyAboutEvent::class],
        CommentDeleted::class => [CommentNotifyAboutEvent::class],
        CommentResolved::class => [CommentNotifyAboutEvent::class],

        JudgementCreated::class => [JudgementNotifyAboutEvent::class],
        JudgementUpdated::class => [JudgementNotifyAboutEvent::class],
        JudgementDeleted::class => [JudgementNotifyAboutEvent::class],

        StepApproved::class => [SendGenesToGtOnGcepApproval::class,
                                RegisterMembersInGtOnGcepApproval::class],
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
