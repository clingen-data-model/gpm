<?php

namespace App\Domain\Application\Aggregates;

use DateTime;
use Exception;
use Ramsey\Uuid\Uuid;
use Infrastructure\Domain\Aggregate;
use App\Domain\Application\Events\ApplicationInitiated;

class Application extends Aggregate
{
    // Deciders
    public static function initiate(
        string $aggregateId, 
        string $workingName, 
        string $cdwgUuid,
        int $epTypeId,
        DateTime $dateInitiated
    )
    {
        $event = new ApplicationInitiated(
            aggregateId: $aggregateId, 
            workingName: $workingName, 
            cdwgUuid: $cdwgUuid, 
            epTypeId: $epTypeId, 
            dateInitiated: $dateInitiated
        );
        $instance = new static();
        $instance->raiseEvent($event);
        return $instance;
    }

    protected function apply($event) {
        switch (get_class($event)) {
            case ApplicationInitiated::class:
                $this->aggregateId = $event->aggregateId;
                $this->workingName = $event->workingName;
                $this->cdwgUuid = $event->cdwgUuid;
                $this->epTypeId = $event->epTypeId;
                $this->dateInitiated = $event->dateInitiated;
                $this->version = 1;
                break;
            
            default:
                throw new Exception('Unknown event, '.get_class($event).', received for '.__CLASS__);
                break;
        }
    }
    
}
