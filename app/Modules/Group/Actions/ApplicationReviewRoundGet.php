<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Services\ApplicationReviewHistory;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class ApplicationReviewRoundGet
{
    use AsController;

    public function authorize(ActionRequest $request): bool
    {
        return app(ApplicationReviewHistoryGet::class)->authorize($request);
    }

    public function asController(Group $group, int $submission): array
    {
        $round = Submission::withTrashed()->where('group_id', $group->id)->findOrFail($submission);
        return app(ApplicationReviewHistory::class)->detail($group, $round);
    }
}
