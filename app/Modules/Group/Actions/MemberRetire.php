<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Events\MemberRetired;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Group\Models\GroupMember;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;

class MemberRetire
{
    use AsObject;
    use AsController;

    public function handle(GroupMember $groupMember, Carbon $endDate): GroupMember
    {
        $groupMember->update(['end_date' => $endDate]);

        Event::dispatch(new MemberRetired($groupMember));

        return $groupMember;
    }

    public function asController(ActionRequest $request, $groupUuid, $groupMemberId)
    {
        $groupMember = GroupMember::findOrFail($groupMemberId);
        $endDate = Carbon::parse($request->endDate);

        $member = $this->handle($groupMember, $endDate);
        $member->load('roles', 'permissions', 'cois', 'person', 'person.institution');
        unset($member->group);

        return new MemberResource($member);
    }

    public function rules(): array
    {
        return [
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_date' => 'required|date',
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            'end_date.after_or_equal' => 'The end date must be a date after or equal to the member\'s start date.',
        ];
    }

    // public function authorize(ActionRequest $request): bool
    // {
    //     return $request->user()->can('retireMember');
    // }
}
