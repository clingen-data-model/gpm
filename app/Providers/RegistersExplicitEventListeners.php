<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;

trait RegistersExplicitEventListeners
{
    /**
     * Registers event listeners for events defined in $this->listeners array.
     *
     * @param array $listenerMap An associative array that maps an Event class to an array Listener classes.
     *
     * @return void
     */
    protected function registerExplicitListeners(array $listenerMap): void
    {
        foreach ($listenerMap as $event => $listeners) {
            foreach (array_unique($listeners) as $listener) {
                Event::listen($event, [$listener, 'handle']);
            }
        }
    }
}
