<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;

trait RegistersExplicitEventListeners
{
    /**
     * Registers event listeners for events defined in $this->listeners array.
     *
     * @return void
     */
    protected function registerExplicitListeners(): void
    {
        foreach ($this->listeners as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, [$listener, 'handle']);
            }
        }
    }
}
