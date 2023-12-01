<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Events\MemberUnretired;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;

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
