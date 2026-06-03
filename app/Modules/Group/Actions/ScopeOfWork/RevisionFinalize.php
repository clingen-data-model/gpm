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
        $user = $user ?: Auth::user();

        if ($revision->group_id !== $group->id) {
            abort(404);
        }

        if ($revision->status !== ScopeOfWorkVersion::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'revision' => 'Only draft Scope of Work revisions can be finalized.',
            ]);
        }

        $revision->load('changes');

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