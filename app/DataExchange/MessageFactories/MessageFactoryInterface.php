<?php

namespace App\DataExchange\MessageFactories;

use App\Events\Event;

interface MessageFactoryInterface
{
    public function make(
        string $eventType,
        array $message,
        ?string $schemaVersion = '1.0.0'
    ): array;
}
