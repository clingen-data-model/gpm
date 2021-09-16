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

class MemberInvite
{
    use AsController;
    use AsObject;
    
    public function __construct(private PersonInvite $invitePerson, private MemberAdd $addMember)
    {
    }

    public function handle(Group $group, array $data)
    {
        [$person, $invite] = $this->invitePerson->handle($data);
        
        $newMember = $this->addMember->handle($group, $person);

        return $newMember;
    }

    public function asController(ActionRequest $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        if (!Auth::user()->person->hasGroupPermissionTo('members-invite', collect([$group]))) {
            abort('403', 'You do not have permission to invite members to this group.');
        }
        return $this->handle($group, $request->all());
    }
}
