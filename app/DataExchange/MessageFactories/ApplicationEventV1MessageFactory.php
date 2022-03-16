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
use App\Modules\Groups\Events\PublishableApplicationEvent;
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

    private function buildMessageFromEvent($event)
    {
        $reflection = new ReflectionClass($event);
        $message = [
            'expert_panel' => [
                'id' => $event->group->uuid,
                'name' => $event->group->displayName,
                'type' => $event->group->fullType->name,
                'affiliation_id' => $event->group->expertPanel->affiliation_id
            ],
        ];

        if ($reflection->implementsInterface(GeneEvent::class)) {
            $message['genes'] = $this->makeGeneData($event->gene);
        }

        if ($event instanceof GroupMemberEvent) {
            $message['members'] = [$this->makeMemberData($event->groupMember)];
        }

        if (get_class($event) == StepApproved::class && $event->step == 1) {
            $message['members'] = $event->group
                                    ->members
                                    ->map(function ($member) {
                                        return $this->makeMemberData($member);
                                    })
                                    ->toArray();
            
            $message['scope']['statement'] = $event->group->expertPanel->scope_description;
            $message['scope']['genes'] = $this->makeGeneData($event->group->expertPanel->genes);
        }

        return $message;
    }

    private function makeMemberData($member): array
    {
        return [
            'id' => $member->person->uuid,
            'first_name' => $member->person->first_name,
            'last_name' => $member->person->last_name,
            'email' => $member->person->email,
            'group_roles' => $member->roles->pluck('name')->toArray(),
            'additional_permissions' => $member->permissions->pluck('name')->toArray(),
        ];
    }

    private function makeGeneData($genes): array
    {
        if (! $genes instanceof Collection) {
            $genes = collect([$genes]);
        }

        return $genes->map(function ($gene) {
            $item = $gene->only([
                'hgnc_id',
                'gene_symbol',
            ]);
            if ($gene->mondo_id) {
                $item['mondo_id'] = $gene->mondo_id;
            }

            if ($gene->mondo_id) {
                $item['disease_entity'] = $gene->mondo_id;
                $item['disease_name'] = $gene->disease_name;
            }

            return $item;
        })->toArray();
    }
}
