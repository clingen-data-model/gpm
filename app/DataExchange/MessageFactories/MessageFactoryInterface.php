<?php

namespace App\DataExchange\MessageFactories;

use Carbon\Carbon;
use App\Events\Event;

interface MessageFactoryInterface
{
    public function make(
        string $eventType,
        array $message,
        Carbon $date,
        ?string $schemaVersion = '1.0.0'
    ): array;
}
