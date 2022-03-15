<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Group\Events\MemberRetired;
use App\Modules\Group\Events\MemberUnretired;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Http\Resources\MemberResource;

class MemberUnretire
{
    use AsObject;
    use AsController;

    public function handle(GroupMember $groupMember): GroupMember
    {
        $groupMember->update(['end_date' => null]);
        
        Event::dispatch(new MemberUnretired($groupMember));
        return $groupMember;
    }

    public function asController(ActionRequest $request, Group $group, $groupMemberId)
    {
        $groupMember = GroupMember::findOrFail($groupMemberId);
        $endDate = Carbon::parse($request->endDate);

        return new MemberResource($this->handle($groupMember, $endDate));
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('retireMember', $request->group);
    }
}
