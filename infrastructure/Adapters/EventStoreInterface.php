<?php

namespace Infrastructure\Adapters;

use Infrastructure\Domain\Aggregate;

interface EventStoreInterface
{
    public function loadEventStream(string $aggregateId): EventStream;

    public function commitChanges(Aggregate $aggregate): void;
}
