<?php

namespace App\Modules\Person\Actions;

use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Actions\MemberGrantPermissions;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class PersonMerge
{
    use AsController;

    public function __construct(private PersonDelete $deletePerson, private MemberAdd $addMember, private MemberAssignRole $assignRoles, private MemberGrantPermissions $grantPermissions)
    {
        //code
    }
    

    public function handle(Person $authority, Person $obsolete): Person
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
        });

        $this->deletePerson->handle($obsolete);

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
}
