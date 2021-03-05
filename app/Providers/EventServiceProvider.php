<?php

namespace App\Providers;

use App\Listeners\RecordEvent;
use App\Listeners\TestListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use App\Modules\Application\Events\ContactAdded;
use App\Modules\Application\Events\StepApproved;
use App\Modules\Application\Events\DocumentAdded;
use App\Modules\Application\Events\ContactRemoved;
use App\Modules\Application\Events\NextActionAdded;
use App\Modules\Application\Events\DocumentReviewed;
use App\Modules\Application\Events\NextActionCompleted;
use App\Modules\Application\Events\ApplicationCompleted;
use App\Modules\Application\Events\ApplicationInitiated;
use App\Modules\Application\Events\ExpertPanelAttributesUpdated;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
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
