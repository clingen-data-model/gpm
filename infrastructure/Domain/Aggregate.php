<?php

namespace Infrastructure\Domain;

use JsonSerializable;

/**
 * @property array $events events that have been applied
 * @property array $changes events that have not yet been committed
 * @property int $version
 */
abstract class Aggregate implements AggregateInterface, JsonSerializable
{

    private array $changes = [];
    private string $aggregateId;

    abstract protected function apply($event);

    public function __construct(private array $events = [], private int $version=0)
    {
        $this->replay();
    }

    protected function replay()
    {
        foreach ($this->events as $event) {
            $this->apply($event);
        }
    }

    protected function raiseEvent($event)
    {
        array_push($this->events, $event);
        array_push($this->changes, $event);
        $this->apply($event);
    }

    public function jsonSerialize()
    {
        $data = [];
        foreach (get_object_vars($this) as $key => $value) {
            
            if (is_object($value)){
                if ($value instanceof JsonSerializable) {
                    $data[$key] = $value->jsonSerialize();
                } elseif (get_class($value) == 'stdClass') {
                    $data[$key] = $value;
                } 
                continue;
            } else {
                $data[$key] = $value;
            }
        }
    }

    public function clearChanges():void
    {
        $this->changes = [];
    }
    

    public function __get($key)
    {
        if ($key == 'events') {
            return $this->events;
        }
        if ($key == 'changes') {
            return $this->changes;
        }
        if ($key == 'version') {
            return $this->version;
        }
    }

    
}
