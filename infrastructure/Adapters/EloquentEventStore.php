<?php

namespace Infrastructure\Adapters;

use Illuminate\Support\Facades\DB;
use Infrastructure\Domain\Aggregate;
use Infrastructure\Adapters\Models\Aggregate as ModelsAggregate;
use Infrastructure\Adapters\Models\StoredEvent;
use Ramsey\Uuid\Uuid;

class EloquentEventStore implements EventStoreInterface
{
    public function __construct()
    {
        //code
    }

    public function loadEventStream(string $aggregateId): EventStream
    {
        $events = [];
        $version = 1;
        // get the aggregate with it's changes.
        // set version
        // set events

        return new EventStream(events: $events, version: $version);
    }

    public function commitChanges(Aggregate $aggregate): void
    {
        $latestStoredVersion = StoredEvent::getLatestVersionForAggregate($aggregate->aggregateId);
        if ($latestStoredVersion != $aggregate->version) {
            throw new ConcurrentWriteException();
        }
        DB::transaction(function () use ($aggregate){
            foreach ($aggregate->changes as $event) {
                $storedEvent = StoredEvent::fromDomainEvent($event, $newVersion);
                $storedEvent->uuid = Uuid::uuid4()->toString();
                $storedEvent->save();
                $aggregate->clearChanges();
            }
        });


        // start a transaction
        // attempt to change aggregate with matching id and version
        // if none throw exception
        // store events or throw exception
    }

}