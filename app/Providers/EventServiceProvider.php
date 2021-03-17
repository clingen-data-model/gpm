<?php

namespace App\Providers;

use App\Listeners\RecordEvent;
use App\Listeners\TestListener;
use App\Modules\Application\Events\ApplicationCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;

use App\Modules\Application\Events\StepApproved;
use App\Modules\Application\Events\ApplicationInitiated;
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
        // StepApproved::class => [
        //     SendStepApprovedNotification::class
        // ],
        // ApplicationCompleted::class => [
        //     SendApplicationCompletedNotification::class
        // ],
        
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
    }
}
