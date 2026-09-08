<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;

class RevisionApprove
{
    use AsObject;
    use AsController;

    public function handle(Group $group, ScopeOfWorkVersion $revision, ?User $user = null): ScopeOfWorkVersion
    {
        $user = $user ?: Auth::user();

        if ($revision->group_id !== $group->id) {
            abort(404);
        }

        if ($revision->status !== ScopeOfWorkVersion::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'revision' => 'Only submitted Scope of Work revisions can be approved.',
            ]);
        }

        return DB::transaction(function () use ($revision, $user) {
            $revision = ScopeOfWorkVersion::whereKey($revision->id)->lockForUpdate()->firstOrFail();
            $submission = $revision->submission;
            if (!$submission || (int) $submission->scope_of_work_version_id !== $revision->id) {
                throw ValidationException::withMessages([
                    'submission' => 'The revision has no current submission to approve.',
                ]);
            }
            app(RevisionApproveFromSubmission::class)->handle($submission, now(), $user?->id);

            return $revision->fresh(['changes', 'latestSnapshot', 'baseVersion', 'submission']);
        });
    }

    public function asController(Group $group, ScopeOfWorkVersion $scopeOfWorkVersion)
    {
        $this->handle($group, $scopeOfWorkVersion);

        return StatusGet::run($group);
    }

    public function authorize(): bool
    {
        return Auth::user()->can('ep-applications-manage');
    }
}
