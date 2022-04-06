<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Person\Events\PersonMerged;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Actions\MemberGrantPermissions;

class PersonMerge
{
    use AsController;

    public function __construct(
        private PersonDelete $deletePerson, 
        private MemberAdd $addMember, 
        private MemberAssignRole $assignRoles, 
        private MemberGrantPermissions $grantPermissions,
        private PersonUnlinkUser $unlinkUser
    )
    {
        //code
    }
    

    public function handle(Person $authority, Person $obsolete): Person
    {

        $this->transferMemberships($authority, $obsolete);
        $this->transferUser($authority, $obsolete);

        $this->deletePerson->handle($obsolete);

        event(new PersonMerged($authority, $obsolete));

        return $authority;
    }

    public function asController(ActionRequest $request)
    {
        $authority = Person::findOrFail($request->authority_id);
        $obsolete = Person::findOrFail($request->obsolete_id);

        $updatedAuthority = $this->handle($authority, $obsolete);
        return $updatedAuthority->load('institution');
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('people-manage');
    }
    
    public function rules(): array
    {
        return [
            'obsolete_id' => 'required|exists:people,id',
            'authority_id' => 'required|exists:people,id|different:obsolete_id',
        ];
    }

    private function transferUser($authority, $obsolete)
    {
        if (!$obsolete->isLinkedToUser()) {
            return;
        }

        if ($authority->isLinkedToUser()) {
            return;
        }

        $authority->update(['user_id' => $obsolete->user_id]);
        
        $obsolete->user->update(['email' => $authority->email]);
        $obsolete = $this->unlinkUser->handle($obsolete);
    }
    

    private function transferMemberships($authority, $obsolete)
    {
        $obsolete->load(['memberships', 'memberships.group', 'memberships.roles', 'memberships.permissions']);
        
        $obsolete->memberships->each(function ($membership) use ($authority) {
            $membershipData = $membership->only([
                'start_date',
                'end_date',
                'is_contact',
                'expertise',
                'notes',
                'training_level_1',
                'training_level_2'
            ]);

            $newMembership = $this->addMember->handle($membership->group, $authority, $membershipData);
            
            if ($membership->roles->count() > 0) {
                $this->assignRoles->handle($newMembership, $membership->roles);
            }
            
            if ($membership->permissions->count() > 0) {
                $this->grantPermissions->handle($newMembership, $membership->permissions);
            }

            if ($membership->cois->count() > 0) {
                $membership->cois->each(function ($coi) use ($newMembership) {
                    $coi->update([
                        'group_member_id' => $newMembership->id
                    ]);
                });
            }
        });
    }
    
}
