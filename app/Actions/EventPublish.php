<?php

namespace App\Actions;

use App\DataExchange\Actions\StreamMessageCreate;
use App\DataExchange\MessageFactories\DxMessageFactory;
use App\DataExchange\Models\StreamMessage;
use App\Events\PublishableEvent;

class EventPublish
{
    public function __construct(
        public StreamMessageCreate $streamMessageCreate,
        public DxMessageFactory $messageFactory
    ) {
    }

    public function handle(PublishableEvent $event): ?StreamMessage
    {
        if (! $event->shouldPublish()) {
            return null;
        }

        $message = $this->messageFactory->makeFromEvent($event);
        if ($event->shouldPublish($event) && $message) {
            return $this->streamMessageCreate->handle(
                topic: $event->getTopic(),
                message: $message
            );
        }

        return null;
    }
}
