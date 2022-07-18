<?php

namespace App\Modules\Person\Actions;

use App\Actions\Contracts\AsFollowAction;
use App\Events\Event;
use App\Models\Permission;
use App\Actions\CreateFollowAction;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Events\InviteRedeemed;

class PermissionAdd implements AsFollowAction
{
    public function __construct(private CreateFollowAction $createFollowAction)
    {
    }
    
    public function handle(Person $person, $permissionName): Person
    {
        if (!$this->isSystemPermission($permissionName)) {
            throw new \InvalidArgumentException('Permission '.$permissionName.' is not a valid system permission.  Only system permissions may be added to users via their person record.');
        }

        if (!$person->isLinkedToUser()) {
            $this->createFollowAction->handle(
                eventClass: InviteRedeemed::class,
                follower: PermissionAdd::class,
                args: ['personId' => $person->id, 'permissionName' => $permissionName],
                name: 'Grant '.$permissionName.' permission to '.$person->name.' when invite redeemed.',
            );
            return $person;
        }

        $person->user->givePermissionTo($permissionName);

        return $person;
    }

    public function asFollowAction(Event $event, ...$args): Person
    {
        extract($args);
        $person = Person::find($personId);
        return $this->handle($person, $permissionName);
    }

    private function isSystemPermission($permissionName): bool
    {
        $perm = Permission::where(['name' => $permissionName])->first();
        return !is_null($perm) && $perm->scope == 'system';
    }
    
    
}
