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
use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Events\GeneRemoved;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Events\MemberRemoved;
use App\Modules\Group\Events\MemberRetired;
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
    const GENE_EVENTS = [
        // GeneAddedApproved::class,
        // GeneRemovedApproved::class
        GenesAdded::class,
        GeneRemoved::class
    ];

    const MEMBER_EVENTS = [
        MemberAdded::class,
        MemberRemoved::class,
        MemberRetired::class,
        MemberRoleAssigned::class,
        MemberRoleRemoved::class,
        MemberPermissionsGranted::class,
        MemberPermissionRevoked::class,
    ];


    public function make(
        string $eventType,
        array $message,
        Carbon $date,
        ?string $schemaVersion = '1.0.0'
    ): array {
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
            eventType: $this->resolveTypeFromEvent($event),
            message: $this->buildMessageFromEvent($event),
            schemaVersion: null,
            date: $event->getLogDate()
        );
    }

    private function resolveTypeFromEvent(PublishableApplicationEvent $event): string
    {
        switch (get_class($event)) {
            case StepApproved::class:
                return $this->resolveStepApprovalEventType($event->step);
            // Cases commented until V2.x
            // case GeneAddedApproved::class:
            //     return 'gene_added';
            // case GeneRemovedApproved::class:
            //     return 'gene_removed';
            case GenesAdded::class:
                return 'gene_added';
            case GeneRemoved::class:
            case MemberAdded::class:
            case MemberRemoved::class:
            case MemberRetired::class:
            case MemberRoleAssigned::class:
            case MemberRoleRemoved::class:
            case MemberPermissionRevoked::class:
                $reflect = new ReflectionClass($event);
                return Str::snake($reflect->getShortName());
                break;
            case MemberPermissionsGranted::class:
                return 'member_permission_granted';
            default:
                return null;
        }
    }

    private function resolveStepApprovalEventType(Int $step): string
    {
        switch ($step) {
            case 1:
                return 'ep_definition_approved';
            case 2:
                return 'vcep_draft_specifications_approved';
            case 3:
                return 'vcep_pilot_approved';
            case 4:
                return 'ep_final_approval';
            default:
                throw new Exception('Invalid step approved expected 1-4, received '.$event->step);
        }
    }
    
    

    private function buildMessageFromEvent($event)
    {
        $eventClass = get_class($event);
        $message = [
            'expert_panel' => [
                'id' => $event->group->uuid,
                'name' => $event->group->displayName,
                'type' => $event->group->fullType->name,
                'affiliation_id' => $event->group->expertPanel->affiliation_id
            ],
        ];

        if (in_array($eventClass, static::GENE_EVENTS)) {
            $message['genes'] = $this->makeGeneData($event->gene);
        }

        if (in_array($eventClass, static::MEMBER_EVENTS)) {
            $message['members'] = [$this->makeMemberData($event->groupMember)];
        }

        if ($eventClass == StepApproved::class && $event->step == 1) {
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
