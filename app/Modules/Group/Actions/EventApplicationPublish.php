<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Events\GeneEvent;
use App\DataExchange\Models\StreamMessage;
use App\Modules\Group\Events\GroupMemberEvent;
use App\DataExchange\Actions\StreamMessageCreate;
use App\Modules\Group\Events\PublishableApplicationEvent;
use App\DataExchange\MessageFactories\ApplicationEventV1MessageFactory;

class EventApplicationPublish
{
    public function __construct(
        public StreamMessageCreate $streamMessageCreate,
        public ApplicationEventV1MessageFactory $messageFactory
    ) {
    }
    
    public function handle(PublishableApplicationEvent $event): ?StreamMessage
    {
        if (!$event->group->isEp) {
            return null;
        }

        $message = $this->messageFactory->makeFromEvent($event);
        if ($this->shouldPublish($event) && $message) {
            return $this->streamMessageCreate->handle(
                topic: config('dx.topics.outgoing.gpm-applications'),
                message: $message
            );
        }

        return null;
    }

    private function shouldPublish($event): bool
    {
        if (!$event->group->isEp) {
            return false;
        }

        if (
            $this->isGroupMemberOrGeneEvent($event)
            && !$this->definitionIsApproved($event->group)
        ) {
            return false;
        }

        return true;
    }

    private function definitionIsApproved(Group $group): bool
    {
        return $group->expertPanel->definitionIsApproved;
    }
    

    private function isGroupMemberOrGeneEvent($event): bool
    {
        return $this->isGroupMemberEvent($event) || $this->isGeneEvent($event);
    }
    

    private function isGroupMemberEvent($event): bool
    {
        return $event instanceof GroupMemberEvent;
    }

    private function isGeneEvent($event): bool
    {
        return $event instanceof GeneEvent;
    }
}
