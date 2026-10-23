<?php

namespace App\Modules\Group\Actions;

use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Http\Resources\ApplicationActivityResource;

class ApplicationActivityGet
{
    use AsController;

    public function handle(ActionRequest $request)
    {
        $user = $request->user();

        $query = Group::query()
                    ->where(function ($q) use ($user) {
                        $statuses = $this->getSubmissionStatusesForUser($user);
                        $q->where(function ($initial) use ($statuses) {
                            $initial->whereHas('expertPanel', fn ($panel) => $panel->applying())
                                ->whereHas('latestSubmission', fn ($submission) => $submission->withStatus($statuses));
                        })->orWhere(function ($revision) use ($statuses) {
                            $revision->whereHas('expertPanel', fn ($panel) => $panel->approved())
                                ->whereHas('activeScopeOfWorkSubmission', fn ($submission) => $submission->withStatus($statuses));
                        });
                    })
                    ->with([
                        'type',
                        'latestSubmission',
                        'latestSubmission.judgements',
                        'latestSubmission.judgements.person' => function ($q) {
                            $q->select('first_name', 'last_name', 'email', 'id');
                        },
                        'activeScopeOfWorkSubmission.judgements.person:id,first_name,last_name,email',
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
