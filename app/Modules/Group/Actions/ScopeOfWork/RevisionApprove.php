<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Models\User;
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
            $revision->update([
                'status' => ScopeOfWorkVersion::STATUS_APPROVED,
                'approved_by' => $user?->id,
                'approved_at' => now(),
            ]);

            if ($revision->submission) {
                $revision->submission->update([
                    'submission_status_id' => config('submissions.statuses.approved.id'),
                    'closed_at' => now(),
                ]);
            }

            return $revision->fresh([
                'changes',
                'latestSnapshot',
                'baseVersion',
                'submission',
            ]);
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