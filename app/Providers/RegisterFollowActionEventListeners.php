<?php

namespace App\Providers;

use App\Actions\FollowActionRun;
use Illuminate\Support\Facades\Event;

trait RegisterFollowActionEventListeners
{
    /**
     * Registers FollowAction listener for all events in $eventClasses
     *
     * @param array $eventClasses Array of event classes.
     *
     * @return void
     */
    protected function registerFollowActionListeners(array $eventClasses): void
    {
        foreach ($eventClasses as $class) {
            Event::listen($class, FollowActionRun::class);
        }
    }

}
