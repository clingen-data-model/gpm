<?php

namespace App\DataExchange\MessageFactories;

use App\Events\Event;
use App\DataExchange\MessageFactories\MessageFactoryInterface;

class GroupEventV1MessageFactory implements MessageFactoryInterface
{
    public function make(
        string $eventType,
        array $message,
        ?string $schemaVersion = '1.0.0'
    ): array {
        $message = [
            'event_type' => $eventType,
            'schema_version' => $schemaVersion,
            'data' => $message,
        ];

        return $message;
    }

    public function makeFromEvent(Event $event): array
    {
        return $this->make(
            eventType: $this->resolveEventType($event),
            message: $this->buildMessage($event),
            schemaVersion: null,
        );
    }

    private function resolveEventType(Event $event): string
    {
        return '';
    }
    

    private function buildMessage($event)
    {
        $message = [];
        return $message;
    }
}
