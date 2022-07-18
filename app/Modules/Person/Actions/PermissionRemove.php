<?php

namespace App\Modules\Person\Actions;

use App\Actions\Contracts\AsFollowAction;
use App\Events\Event;
use App\Models\Permission;
use App\Actions\CreateFollowAction;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Events\InviteRedeemed;

class PermissionRemove implements AsFollowAction
{
    public function handle(Person $person, $permissionName): Person
    {
        if (!$this->isSystemPermission($permissionName)) {
            throw new \InvalidArgumentException('Permission '.$permissionName.' is not a valid system permission.  Only system permissions may be added to users via their person record.');
        }

        $person->user->revokePermissionTo($permissionName);

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
        return !$perm || $perm->scope == 'system';
    }
    
    
}
