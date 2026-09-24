<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkChange;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;

class RevisionFinalize
{
    use AsObject;
    use AsController;

    public function handle(Group $group, ScopeOfWorkVersion $revision, ?User $user = null): ScopeOfWorkVersion
    {
        \App\Modules\Group\Services\ScopeOfWorkEligibility::requireCompleted($group);
        return DB::transaction(function () use ($group, $revision, $user) {
            $revision = ScopeOfWorkVersion::whereKey($revision->id)->lockForUpdate()->firstOrFail();
            return $this->finalize($group, $revision, $user);
        });
    }

    private function finalize(Group $group, ScopeOfWorkVersion $revision, ?User $user): ScopeOfWorkVersion
    {
        $user = $user ?: Auth::user();

        if ($revision->group_id !== $group->id) {
            abort(404);
        }

        if (!in_array($revision->status, [ScopeOfWorkVersion::STATUS_DRAFT, ScopeOfWorkVersion::STATUS_REVISIONS_REQUESTED], true)) {
            throw ValidationException::withMessages([
                'revision' => 'Only draft or revisions-requested Scope of Work revisions can be finalized.',
            ]);
        }

        $revision->load('changes');

        if ($revision->submissions()->pending()->exists()) {
            throw ValidationException::withMessages(['revision' => 'A Scope of Work revision under review cannot be finalized.']);
        }

        if ($revision->changes->isEmpty()) {
            throw ValidationException::withMessages([
                'revision' => 'This Scope of Work revision does not have any changes to finalize.',
            ]);
        }

        $requiresApproval = $revision->changes->contains(function (ScopeOfWorkChange $change) {
            return in_array($change->requires_approval, [
                ScopeOfWorkChange::APPROVAL_YES,
                ScopeOfWorkChange::APPROVAL_CONDITIONAL,
            ], true);
        });

        if ($requiresApproval) {
            throw ValidationException::withMessages([
                'revision' => 'This Scope of Work revision requires approval and cannot be finalized directly.',
            ]);
        }

        return DB::transaction(function () use ($revision, $user) {
            $revision->update([
                'status' => ScopeOfWorkVersion::STATUS_APPROVED,
                'approved_by' => $user?->id,
                'approved_at' => now(),
            ]);

            return $revision->fresh([
                'changes',
                'latestSnapshot',
                'baseVersion',
            ]);
        });
    }

    public function asController(Group $group, ScopeOfWorkVersion $scopeOfWorkVersion)
    {
        return $this->handle($group, $scopeOfWorkVersion);
    }
}
