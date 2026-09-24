<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Services\ApplicationReviewHistory;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class ApplicationReviewHistoryGet
{
    use AsController;

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('view', $request->group)
            && ($request->user()->can('ep-applications-manage') || $request->user()->can('ep-applications-approve')
                || $request->user()->can('ep-applications-comment'));
    }

    public function asController(Group $group): array
    {
        return app(ApplicationReviewHistory::class)->handle($group);
    }
}
