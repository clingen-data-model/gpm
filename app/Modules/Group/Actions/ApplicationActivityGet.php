<?php

namespace App\Modules\Group\Actions;

use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\Submission;
use Lorisleiva\Actions\Concerns\AsController;
use App\Http\Resources\ApplicationActivityResource;

class ApplicationActivityGet
{
    use AsController;

    public function handle(ActionRequest $request)
    {
        $user = $request->user();
        
        $query = Group::query()
                    ->whereHas('latestSubmission', function ($q) use ($user) {
                        $q->withStatus($this->getSubmissionStatusesForUser($user));
                    })
                    ->with([
                        'type',
                        'latestSubmission',
                        'expertPanel' => function ($q) {
                            $q->select(['id', 'uuid', 'long_base_name', 'short_base_name', 'group_id', 'current_step']);
                        }
                    ]);

        return ApplicationActivityResource::collection($query->get());
        
    }

    public function authorize(ActionRequest $request):bool
    {
        return $request->user()->hasAnyPermission(['ep-applications-manage', 'ep-applications-comment', 'ep-applications-approve']);
    }

    private function getSubmissionStatusesForUser(User $user): array
    {
        if ($user->hasPermissionTo('ep-applications-approve')) {
            return [config('submissions.statuses.under-chair-review.id')];
        }

        $statuses = [config('submissions.statuses.under-chair-review.id')];

        if (
            $user->hasAnyPermission(['ep-applications-manage', 'ep-applications-comment'])
        ) {
            $statuses[] = config('submissions.statuses.pending.id');
        }
        if ($user->hasPermissionTo('ep-applications-manage')) {
            $statuses[] = config('submissions.statuses.revisions-requested.id');
        }
        
        return $statuses;
    }
    

}