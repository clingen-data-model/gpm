<?php

namespace App\Actions;

use App\Events\PublishableEvent;
use App\DataExchange\Models\StreamMessage;
use App\DataExchange\Actions\StreamMessageCreate;
use App\DataExchange\MessageFactories\DxMessageFactory;

class EventPublish
{
    public function __construct(
        public StreamMessageCreate $streamMessageCreate,
        public DxMessageFactory $messageFactory
    ) {
    }

    public function handle(PublishableEvent $event): ?StreamMessage
    {
        if (!$event->shouldPublish()) {
            return null;
        }

        $message = $this->messageFactory->makeFromEvent($event);
        if ($event->shouldPublish($event) && $message) {
            $streamMessage = $this->streamMessageCreate->handle(
                topic: $event->getTopic(),
                message: $message,
                eventUuid: $event->getEventUuid()
            );
            // TODO: check that we don't have a race condition between the primary event and checkpointing
            $event->checkpointIfNeeded();
            return $streamMessage;
        }

        return null;
    }
}
