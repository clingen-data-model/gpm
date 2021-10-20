<?php
namespace App\Modules\Group\Actions;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\Group\Events\MemberUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\Group\Http\Resources\MemberResource;

class MemberUpdate
{
    use AsAction;

    public function handle(GroupMember $groupMember, array $data): GroupMember
    {
        $groupMember->update($data);

        Event::dispatch(new MemberUpdated($groupMember, $data));

        return $groupMember;
    }

    public function asController($groupId, $id, Request $request)
    {
        $group = Group::findByUuidOrFail($groupId);
        $groupMember = $group->members()->findOrFail($id);
        if (Auth::user()->cannot('updateMembers', $group)) {
            throw new AuthorizationException('You do not have permission to update members of this group.');
        }

        $member = $this->handle($groupMember, $request->all());
        $member->load(['cois', 'roles', 'permissions']);
        
        return new MemberResource($member);
    }
}
