<?php

namespace App\Modules\Person\Actions;

use App\Actions\Contracts\AsFollowAction;
use App\Events\Event;
use App\Models\Permission;
use App\Actions\FollowActionCreate;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Events\InviteRedeemed;

class PermissionAdd implements AsFollowAction
{
    public function __construct(private FollowActionCreate $FollowActionCreate)
    {
    }

    public function handle(Person $person, $permissionName): Person
    {
        if (!$this->isSystemPermission($permissionName)) {
            throw new \InvalidArgumentException('Permission '.$permissionName.' is not a valid system permission.  Only system permissions may be added to users via their person record.');
        }

        if (!$person->isLinkedToUser()) {

            $existing = FollowAction::query()
                ->where('event_class', InviteRedeemed::class)
                ->where('follower', PermissionAdd::class)
                ->where('args->personId', $person->id)
                ->where('args->permissionName', $permissionName)
                ->whereNull('completed_at')
                ->first();

            if (!$existing) {
                $this->FollowActionCreate->handle(
                    eventClass: InviteRedeemed::class,
                    follower: PermissionAdd::class,
                    args: ['personId' => $person->id, 'permissionName' => $permissionName],
                    name: 'Grant '.$permissionName.' permission to '.$person->name.' when invite redeemed.',
                );
            }
            return $person;
        }

        $person->user->givePermissionTo($permissionName);

        return $person;
    }

    public function asFollowAction(Event $event, ...$args): Person
    {
        extract($args);

        if (!property_exists($event, 'person') || $event->person->id !== $personId) {
            // Not match, Ignore it
            return null;
        }
        $person = Person::find($personId);
        return $this->handle($person, $permissionName);
    }

    private function isSystemPermission($permissionName): bool
    {
        $perm = Permission::where(['name' => $permissionName])->first();
        return !is_null($perm) && $perm->scope == 'system';
    }


}
