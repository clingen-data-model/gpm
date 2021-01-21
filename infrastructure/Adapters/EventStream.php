<?php

namespace Infrastructure\Adapters;

class EventStream
{
    public function __construct(private array $events, private int $version)
    {}

    public function getEvents()
    {
        return $this->events;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function __get($key)
    {
        if ($key == 'events') {
            return $this->events;
        }

        if ($key == 'version') {
            return $this->version;
        }
    }
    
    
}
