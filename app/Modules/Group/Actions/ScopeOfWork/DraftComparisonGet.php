<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Models\ApplicationSnapshot;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Services\ScopeOfWorkDisplayComparison;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class DraftComparisonGet
{
    use AsController;

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('updateApplicationAttribute', $request->group);
    }

    public function asController(Group $group, ScopeOfWorkVersion $scopeOfWorkVersion): array
    {
        return $this->handle($group, $scopeOfWorkVersion);
    }

    public function handle(Group $group, ScopeOfWorkVersion $revision): array
    {
        abort_unless($revision->group_id === $group->id, 404);
        abort_unless(in_array($revision->status, [
            ScopeOfWorkVersion::STATUS_DRAFT,
            ScopeOfWorkVersion::STATUS_REVISIONS_REQUESTED,
        ], true), 409, 'A draft or revisions-requested revision is required.');

        $submission = Submission::withTrashed()
            ->where('group_id', $group->id)
            ->where('scope_of_work_version_id', $revision->id)
            ->where('data->context', 'scope_of_work_revision')
            ->orderByDesc('id')->first();

        $baseline = null;
        if ($submission) {
            $before = $this->applicationSnapshot($submission);
        } else {
            $baseline = ScopeOfWorkVersion::withTrashed()
                ->where('group_id', $group->id)
                ->whereKey($revision->base_version_id)
                ->where('status', ScopeOfWorkVersion::STATUS_APPROVED)
                ->first();
            $before = $baseline?->latestSnapshot;
        }

        // Read saved state; never create or refresh a revision on GET.
        $after = SnapshotBuild::run($group->fresh());
        $comparison = app(ScopeOfWorkDisplayComparison::class)
            ->handle($before?->snapshot, $after);

        // Initially expose only contextual name and scope fields.
        $sections = [
            'group.name',
            'expert_panel.long_base_name',
            'expert_panel.short_base_name',
            'scope_description',
        ];
        $changes = array_values(array_filter($comparison['changes'],
            fn ($change) => in_array($change['section'], $sections, true)));
        $unavailable = array_values(array_intersect(
            $comparison['unavailable_sections'], $sections));

        return [
            'source' => 'live',
            'mode' => $submission ? 'previous_review_round' : 'approved_baseline',
            'before' => [
                'submission_id' => $submission?->id,
                'snapshot_type' => $submission ? 'application' : 'scope_of_work',
                'snapshot_id' => $before?->id,
                'captured_at' => $before?->created_at?->toISOString(),
                'scope_of_work_version_id' => $baseline?->id,
            ],
            'after' => [
                'submission_id' => null,
                'snapshot_type' => 'live',
                'snapshot_id' => null,
                'captured_at' => $after['created_at'],
                'scope_of_work_version_id' => $revision->id,
            ],
            'status' => $unavailable ? 'partial' : 'complete',
            'unavailable_sections' => $unavailable,
            'summary' => ['changed_items' => count($changes)],
            'changes' => $changes,
        ];
    }

    private function applicationSnapshot(Submission $submission): ?ApplicationSnapshot
    {
        $query = ApplicationSnapshot::where('group_id', $submission->group_id)
            ->where('submission_id', $submission->id)->whereNull('deleted_at');
        $id = data_get($submission->data, 'application_snapshot_id');
        if ($id !== null) {
            return $query->whereKey($id)->first();
        }
        $snapshots = $query->limit(2)->get();
        return $snapshots->count() === 1 ? $snapshots->first() : null;
    }
}
