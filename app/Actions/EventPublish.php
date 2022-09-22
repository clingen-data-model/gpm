<?php

namespace App\Actions;

use App\Events\PublishableEvent;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Events\GeneEvent;
use App\DataExchange\Models\StreamMessage;
use App\Modules\Group\Events\GroupMemberEvent;
use App\DataExchange\Actions\StreamMessageCreate;
use App\DataExchange\MessageFactories\ApplicationEventV1MessageFactory;

class EventPublish
{
    public function __construct(
        public StreamMessageCreate $streamMessageCreate,
        public ApplicationEventV1MessageFactory $messageFactory
    ) {
    }

    public function handle(PublishableEvent $event): ?StreamMessage
    {
        if (!$event->shouldPublish()) {
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
