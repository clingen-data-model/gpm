<?php

namespace App\DataExchange\MessageFactories;

use Carbon\Carbon;
use App\Events\PublishableEvent;
use App\DataExchange\MessageFactories\MessageFactoryInterface;

class ApplicationEventV1MessageFactory implements MessageFactoryInterface
{
    public function make(
        string $eventType,
        array $message,
        Carbon $date,
        ?string $schemaVersion = null
    ): array {
        $schemaVersion = $schemaVersion ?? config('dx.schema_versions.gpm-general-events');
        $message = [
            'event_type' => $eventType,
            'schema_version' => $schemaVersion,
            'date' => $date->format('Y-m-d H:i:s'),
            'data' => $message,
        ];

        return $message;
    }

    public function makeFromEvent(PublishableEvent $event): array
    {
        return $this->make(
            eventType: $event->getEventType(),
            message: $event->getPublishableMessage(),
            schemaVersion: null,
            date: $event->getLogDate()
        );
    }
}
