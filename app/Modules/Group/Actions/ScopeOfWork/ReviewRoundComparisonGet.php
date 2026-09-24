<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Models\ApplicationSnapshot;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Services\ScopeOfWorkDisplayComparison;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsController;

class ReviewRoundComparisonGet
{
    use AsController;

    public function handle(Group $group, Submission $submission): array
    {
        abort_unless($submission->group_id === $group->id, 404);
        if (data_get($submission->data, 'context') !== 'scope_of_work_revision'
            || !$submission->scope_of_work_version_id) {
            throw ValidationException::withMessages(['submission' => 'A Scope of Work revision submission is required.']);
        }

        $previous = Submission::withTrashed()->where('group_id', $group->id)
            ->where('scope_of_work_version_id', $submission->scope_of_work_version_id)
            ->where('id', '<', $submission->id)->orderByDesc('id')->first();
        $after = $this->applicationSnapshot($submission);
        if ($previous) {
            $before = $this->applicationSnapshot($previous);
            $beforeMetadata = $this->metadata($before, 'application', $previous->id);
        } else {
            $baseline = ScopeOfWorkVersion::withTrashed()->where('group_id', $group->id)
                ->whereKey(data_get($submission->data, 'base_version_id'))
                ->where('status', ScopeOfWorkVersion::STATUS_APPROVED)->first();
            $before = $baseline?->latestSnapshot;
            $beforeMetadata = $this->metadata($before, 'scope_of_work', null);
            $beforeMetadata['scope_of_work_version_id'] = $baseline?->id;
        }

        $comparison = app(ScopeOfWorkDisplayComparison::class)->handle($before?->snapshot, $after?->snapshot);
        return array_merge([
            'mode' => $previous ? 'previous_review_round' : 'approved_baseline',
            'before' => $beforeMetadata,
            'after' => $this->metadata($after, 'application', $submission->id),
        ], $comparison);
    }

    public function asController(Group $group, Submission $submission): array
    {
        return $this->handle($group, $submission);
    }

    private function applicationSnapshot(Submission $submission): ?ApplicationSnapshot
    {
        return app(\App\Modules\Group\Services\ApplicationSnapshotResolver::class)
            ->resolve($submission)['snapshot'];
    }

    private function metadata($snapshot, string $type, ?int $submissionId): array
    {
        return [
            'submission_id' => $submissionId,
            'snapshot_type' => $type,
            'snapshot_id' => $snapshot?->id,
            'captured_at' => $snapshot?->created_at?->toISOString(),
        ];
    }
}
