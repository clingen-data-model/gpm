<?php

namespace App\DataExchange\MessageFactories;

use Exception;
use ReflectionClass;
use App\Events\Event;
use Illuminate\Support\Str;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Events\MemberRemoved;
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
        GeneAddedApproved::class,
        GeneRemovedApproved::class
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
        ?string $schemaVersion = '1.0.0'
    ): array {
        $message = [
            'event_type' => $eventType,
            'schema_version' => $schemaVersion,
            'data' => $message,
        ];

        return $message;
    }

    public function makeFromEvent(PublishableApplicationEvent $event): array
    {
        return $this->make(
            eventType: $this->resolveEventType($event),
            message: $this->buildMessage($event),
            schemaVersion: null,
        );
    }

    private function resolveEventType(PublishableApplicationEvent $event): string
    {
        switch (get_class($event)) {
            case StepApproved::class:
                return $this->resolveStepApprovalEventType($event);
            case GeneAddedApproved::class:
                return 'gene-added';
            case GeneRemovedApproved::class:
                return 'gene-removed';
            case MemberAdded::class:
            case MemberRemoved::class:
            case MemberRetired::class:
            case MemberRoleAssigned::class:
            case MemberRoleRemoved::class:
            case MemberPermissionRevoked::class:
                $reflect = new ReflectionClass($event);
                return Str::kebab($reflect->getShortName());
                break;
            case MemberPermissionsGranted::class:
                return 'member-permission-granted';
            default:
                return null;
        }
    }

    private function resolveStepApprovalEventType(Event $event): string
    {
        switch ($event->step) {
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
    
    

    private function buildMessage($event)
    {
        $eventClass = get_class($event);
        $message = [
            'expert_panel' => [
                'id' => $event->group->uuid,
                'name' => $event->group->displayName,
                'type' => $event->group->fullType->name,
                'affiliation_id' => $event->group->expertPanel->affiliation_id
            ]
        ];

        if (in_array($eventClass, static::GENE_EVENTS)) {
            $message['genes'] = [$this->makeGeneData($event->gene)];
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
            $message['scope']['genes'] = $event->group
                                    ->expertPanel
                                    ->genes
                                    ->map(function ($gene) {
                                        return $this->makeGeneData($gene);
                                    })
                                    ->toArray();
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

    private function makeGeneData($gene): array
    {
        if (is_array($gene) && count($gene) == 1) {
            $gene = $gene[0];
        }

        $returnValue = $gene->only([
                            'hgnc_id',
                            'gene_symbol',
                        ]);
        if ($gene->mondo_id) {
            $returnValue['mondo_id'] = $gene->mondo_id;
        }

        if ($gene->mondo_id) {
            $returnValue['disease_entity'] = $gene->mondo_id;
            $returnValue['disease_name'] = $gene->disease_name;
        }

        return $returnValue;
    }
}
