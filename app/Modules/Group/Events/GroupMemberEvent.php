<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;
use App\Modules\Group\Models\GroupMember;

abstract class GroupMemberEvent extends GroupEvent implements PublishableApplicationEvent
{
    use IsPublishableApplicationEvent {
        getPublishableMessage as protected getBaseMessage;
    }

    public function __construct(public GroupMember $groupMember)
    {
        $this->group = $groupMember->group;
    }

    public function getPublishableMessage(): array
    {
        $message = $this->getBaseMessage();

        // NOTE: members is an array for consistency of message schema.
        $message['members'] = [[
            'id' => $this->groupMember->person->uuid,
            'first_name' => $this->groupMember->person->first_name,
            'last_name' => $this->groupMember->person->last_name,
            'email' => $this->groupMember->person->email,
            'group_roles' => $this->groupMember->roles->pluck('name')->toArray(),
            'additional_permissions' => $this->groupMember->permissions->pluck('name')->toArray(),
        ]];

        return $message;
    }

    /**
     * For PublishableEvent interface that is applied to many sub-classes
     */
    public function shouldPublish(): bool
    {
        return parent::shouldPublish()
            && $this->group->expertPanel->definitionIsApproved;
    }

}
