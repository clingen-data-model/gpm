<?php

namespace App\Modules\Group\Actions;

use App\Events\Event;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Person\Actions\PermissionAdd;

class MemberAddSystemPermission
{
    
    public function __construct(private PermissionAdd $addPermissionToPerson)
    {
    }
    
    public function handle(GroupMember $member, string $permissionName, int $groupId)
    {
        if (!$this->memberOfGroup($member, $groupId)) {
            return;
        }

        $this->addPermissionToPerson->handle($member->person, $permissionName);
    }

    public function asFollowAction(Event $event, ...$args): bool
    {
        extract($args);
        $this->handle($event->groupMember, $permissionName, $groupId);
        return false;
    }

    public function getFollowActionParams():array
    {
        return [
            'permissionName' => [
                'type' => 'string',
                'required' => true
            ],
            'groupId' => [
                'type' => 'integer',
                'label' => 'Group',
                'required' => true,
                'options' => Group::select(['name', 'id'])->get()->map(fn ($g) => ['label' => $g->displayName, 'value' => $g->id])
            ]
        ];
    }

    private function memberOfGroup($member, $groupId): bool
    {
        return $member->group_id == $groupId;
    }
}
