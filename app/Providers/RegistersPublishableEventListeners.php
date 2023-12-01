<?php

namespace App\Providers;

use App\Actions\EventPublish;
use App\Events\PublishableEvent;
use Illuminate\Support\Facades\Event;

trait RegistersPublishableEventListeners
{
    /**
     * Registers EventPublish listener for all PublishableEvents in $eventClasses
     *
     * @param  array  $eventClasses Array of event classes.
     */
    protected function registerPublishableEventListeners(array $eventClasses): void
    {
        foreach ($eventClasses as $class) {
            if (array_key_exists(PublishableEvent::class, class_implements($class))) {
                Event::listen($class, [EventPublish::class, 'handle']);
            }
        }
    }
}
