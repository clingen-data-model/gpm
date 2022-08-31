<?php

namespace App\DataExchange\MessageFactories;

use Exception;
use Carbon\Carbon;
use ReflectionClass;
use App\Events\Event;
use App\Models\Activity;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Events\GeneEvent;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Events\GeneRemoved;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Events\MemberRemoved;
use App\Modules\Group\Events\MemberRetired;
use App\Modules\Group\Events\MemberUnretired;
use App\Modules\Group\Events\GroupMemberEvent;
use App\Modules\Group\Events\GeneAddedApproved;
use App\Modules\Group\Events\MemberRoleRemoved;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\Group\Events\MemberRoleAssigned;
use App\Modules\Group\Events\GeneRemovedApproved;
use App\Modules\Group\Events\MemberPermissionRevoked;
use App\Modules\Group\Events\MemberPermissionsGranted;
use App\Modules\Group\Events\PublishableApplicationEvent;
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

    public function makeFromEvent(PublishableApplicationEvent $event): array
    {
        return $this->make(
            eventType: $event->getEventType(),
            message: $this->buildMessageFromEvent($event),
            schemaVersion: null,
            date: $event->getLogDate()
        );
    }

    private function buildMessageFromEvent(PublishableApplicationEvent $event)
    {
        return $event->getPublishableMessage();
    }

}
