<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Events\MemberRemoved;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;

class MemberRemove
{
    use AsObject;
    use AsController;

    public function handle(GroupMember $groupMember, Carbon $endDate): GroupMember
    {
        $groupMember->update(['end_date' => $endDate]);
        
        Event::dispatch(new MemberRemoved($groupMember));
        return $groupMember;
    }

    public function asController(ActionRequest $request, $groupUuid, $groupMemberId)
    {
        $groupMember = GroupMember::findOrFail($groupMemberId);
        $endDate = Carbon::parse($request->endDate);

        return $this->handle($groupMember, $endDate);
    }

    public function rules(): array
    {
        return [
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }
}
