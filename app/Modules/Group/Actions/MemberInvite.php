<?php

namespace App\Modules\Group\Actions;

use Ramsey\Uuid\Uuid;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Invite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Actions\MemberAdd;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Group\Events\MemberInvited;
use App\Modules\Person\Actions\PersonCreate;
use App\Modules\Person\Actions\PersonInvite;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Http\Resources\MemberResource;

class MemberInvite
{
    use AsController;
    use AsObject;
    
    public function __construct(private PersonInvite $invitePerson, private MemberAdd $addMember, private MemberAssignRole $assignRole)
    {
    }

    public function handle(Group $group, array $data)
    {
        $roleIds = null;
        if (isset($data['role_ids'])) {
            $roleIds = $data['role_ids'];
            unset($data['role_ids']);
        }

        $inviteData = $data;
        $inviteData['inviter_type'] = get_class($group);
        $inviteData['inviter_id'] = $group->id;
        [$person, $invite] = $this->invitePerson->handle($inviteData);
        
        $newMember = $this->addMember->handle($group, $person);

        if ($roleIds) {
            $newMember = $this->assignRole->handle($newMember, $roleIds);
        }

        return $newMember;
    }

    public function asController(ActionRequest $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        if (Auth::user()->person && !Auth::user()->person->hasGroupPermissionTo('members-invite', collect([$group]))) {
            abort('403', 'You do not have permission to invite members to this group.');
        }
        return new MemberResource($this->handle($group, $request->all()));
    }
}
