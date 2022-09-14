<?php

namespace App\Modules\Group\Actions;

use App\Events\Event;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Person\Actions\PermissionAdd;
use App\Modules\Person\Actions\PermissionRemove;

class MemberRemoveSystemPermission
{
    
    public function __construct(private PermissionRemove $removePermissionFromPerson)
    {
    }
    
    public function handle(GroupMember $member, string $permissionName, int $groupId)
    {
        // dump(__METHOD__);
        if (!$this->memberOfGroup($member, $groupId)) {
            return;
        }

        if (!$member->person->isLinkedToUser()) {
            return;
        }

        $this->removePermissionFromPerson->handle($member->person, $permissionName);
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
