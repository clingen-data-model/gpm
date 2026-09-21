<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Services\ScopeOfWorkChangeRestorer;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class RevisionChangeDiscard
{
    use AsController;

    public function handle(Group $group, ScopeOfWorkVersion $revision, int $changeId, User $user): array
    {
        return DB::transaction(function () use ($group, $revision, $changeId, $user) {
            $revision = ScopeOfWorkVersion::whereKey($revision->id)->lockForUpdate()->firstOrFail();
            abort_unless($revision->group_id === $group->id, 404);
            abort_unless(in_array($revision->status, [ScopeOfWorkVersion::STATUS_DRAFT,
                ScopeOfWorkVersion::STATUS_REVISIONS_REQUESTED], true), 409, 'The revision is no longer editable.');
            abort_if($revision->submissions()->pending()->exists(), 409, 'The revision is under review.');
            $change = $revision->changes()->whereKey($changeId)->lockForUpdate()->first();
            abort_unless($change, 409, 'The revision changed. Refresh and retry.');
            abort_unless(ScopeOfWorkChangeRestorer::canDiscard($user, $group, $change), 403);

            $baseline = ScopeOfWorkVersion::forGroup($group)->approved()->whereKey($revision->base_version_id)->first();
            if (!$baseline?->latestSnapshot) {
                throw ValidationException::withMessages(['baseline' => 'The recorded approved snapshot is unavailable.']);
            }
            abort_unless(ScopeOfWorkVersion::latestApprovedForGroup($group)?->id === $baseline->id, 409,
                'The approved baseline changed. Refresh and retry.');
            abort_unless($revision->latestSnapshot, 409, 'The draft snapshot is unavailable. Refresh and retry.');

            $group = Group::whereKey($group->id)->lockForUpdate()->firstOrFail();
            $panel = $group->expertPanel()->lockForUpdate()->firstOrFail();
            $group->setRelation('expertPanel', $panel);
            app(ScopeOfWorkChangeRestorer::class)->restore($group, $change,
                $baseline->latestSnapshot->snapshot, $revision->latestSnapshot->snapshot, SnapshotBuild::run($group));

            RevisionRefresh::run($group->fresh(), $user);
            return StatusGet::run($group->fresh());
        });
    }

    public function asController(ActionRequest $request, Group $group, ScopeOfWorkVersion $scopeOfWorkVersion, int $changeId): array
    {
        return $this->handle($group, $scopeOfWorkVersion, $changeId, $request->user());
    }
}
