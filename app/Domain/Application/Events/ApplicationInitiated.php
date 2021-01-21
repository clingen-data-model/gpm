<?php

namespace App\Domain\Application\Events;

use DateTime;
use Infrastructure\Domain\Event;

class ApplicationInitiated extends Event
{
    public function __construct(
        public string $aggregateId, 
        public string $workingName, 
        public string $cdwgUuid, 
        public int $epTypeId,
        public DateTime $dateInitiated
    )
    {}
}
