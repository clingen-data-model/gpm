<?php

namespace Tests\Fakes;

use Infrastructure\Domain\Event;

class TestDomainEvent extends Event
{
    public function __construct(public string $aggregateId, public array $beans)
    {
    }
}