<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\Group\Models\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;

class CurationReviewProtocolUpdate
{
    use AsObject;
    use AsController;

    public function handle(Group $group, $data): Group
    {
        $group->expertPanel->update($data);
        return $group;
    }

    public function asController(ActionRequest $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        if (Auth::user()->cannot('updateCurationReviewProtocol', $group)) {
            throw new AuthorizationException('You do not have permission to update this EPs curation review protocol.');
        }

        return new GroupResource($this->handle($group, $request->all()));
    }

    public function rules()
    {
        return [
            'curation_review_protocol_id' => 'required|exists:curation_review_protocols,id',
            'curation_review_protocol_other' => 'required_if:curation_review_protocol_id,100',
            'meeting_frequency' => 'required_if:expert_panel_type_id,2|max:255'
        ];
    }

    public function getValidationMessages()
    {
        return [
            'required' => 'This field is required.',
            'required_if' => 'This field is required.',
            'curation_review_protocol_id.exists' => 'The selected protocol is invalid.'
        ];
    }
}
