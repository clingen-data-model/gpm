<?php

declare(strict_types=1);

namespace App\Domain\Application\Commands;

use DateTime;
use Infrastructure\Domain\Command;
use Infrastructure\Adapters\EventStoreInterface;
use App\Domain\Application\Aggregates\Application;

class InitiateApplication extends Command
{
    public function __construct(
        private string $aggregateId,
        private string $workingName,
        private string $cdwgUuid,
        private int $epTypeId,
        private ?DateTime $dateInitiated = null,
        private EventStoreInterface $eventStore
    )
    {}

    public function handle(): void
    {
        $application = Application::initiate(...get_object_vars($this));
    }
}
