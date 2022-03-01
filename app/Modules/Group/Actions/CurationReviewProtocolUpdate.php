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
        $data = collect($data);
        $group->expertPanel->update([
            'curation_review_protocol_id' => $data->get('curation_review_protocol_id'),
            'curation_review_protocol_other' => $data->get('curation_review_protocol_other'),
            'meeting_frequency' => $data->get('meeting_frequency'),
            'curation_review_process_notes' => $data->get('curation_review_process_notes'),
        ]);
        return $group;
    }

    public function asController(ActionRequest $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        if (Auth::user()->cannot('updateCurationReviewProtocol', $group)) {
            throw new AuthorizationException('You do not have permission to update this EPs curation review protocol.');
        }

        $data = $request->only([
            'curation_review_protocol_id',
            'curation_review_protocol_other',
            'meeting_frequency',
            'curation_review_process_notes',
        ]);

        return new GroupResource($this->handle($group, $data));
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
            'required' => 'This is required.',
            'required_if' => 'This is required.',
        ];
    }
}
