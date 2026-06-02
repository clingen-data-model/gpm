<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Group\Events\MemberRemoved;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Group\Actions\ScopeOfWorkRevisionGuard;

class MemberRemove
{
    use AsObject;
    use AsController;

    public function __construct(
        private ScopeOfWorkRevisionGuard $scopeOfWorkRevisionGuard,
    ) {
    }

    public function handle(GroupMember $groupMember, Carbon $endDate): GroupMember
    {
        $this->scopeOfWorkRevisionGuard->ensureNotUnderReview($groupMember->group);
        $groupMember->update(['end_date' => $endDate]);
        $groupMember->delete();
        
        Event::dispatch(new MemberRemoved($groupMember));
        return $groupMember;
    }

    public function asController(ActionRequest $request, $groupUuid, $groupMemberId)
    {
        $groupMember = GroupMember::findOrFail($groupMemberId);
        $endDate = Carbon::parse($request->endDate);

        return new MemberResource($this->handle($groupMember, $endDate));
    }

    public function rules(): array
    {
        return [
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }
}
