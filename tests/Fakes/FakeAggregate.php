<?php

namespace Tests\Fakes;

use Tests\Fakes\TestDomainEvent;
use Infrastructure\Domain\Aggregate;

class FakeAggregate extends Aggregate
{
    public array $beans = [];

    public function addTestEvent(TestDomainEvent $event) {
        $this->raiseEvent($event);
    }

    protected function apply($event)
    {
        if (is_null($this->aggregateId)) {
            $this->aggregateId = $event->aggregateId;
        }

        if (get_class($event) == TestDomainEvent::class) {
            $this->beans = array_merge($this->beans, $event->beans);
        }
    }
    
}
