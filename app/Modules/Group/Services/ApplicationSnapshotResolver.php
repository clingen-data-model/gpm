<?php

namespace App\Modules\Group\Services;

use App\Models\ApplicationSnapshot;
use App\Modules\Group\Models\Submission;

class ApplicationSnapshotResolver
{
    public function resolve(Submission $submission): array
    {
        $query = ApplicationSnapshot::where('group_id', $submission->group_id)
            ->where('submission_id', $submission->id)->whereNull('deleted_at');
        $id = data_get($submission->data, 'application_snapshot_id');
        if ($id !== null) {
            $snapshot = $query->whereKey($id)->first();
            return $this->validate($submission, $snapshot);
        }
        $snapshots = $query->limit(2)->get();
        if ($snapshots->count() === 1) return $this->validate($submission, $snapshots->first());
        return [
            'snapshot' => $snapshots->count() === 1 ? $snapshots->first() : null,
            'availability' => match ($snapshots->count()) {
                0 => 'unavailable', 1 => 'available', default => 'ambiguous',
            },
        ];
    }

    private function validate(Submission $submission, ?ApplicationSnapshot $snapshot): array
    {
        $step = app(ApplicationReviewPresentation::class)->step($submission);
        $capturedStep = data_get($snapshot?->snapshot, 'review_context.step');
        $capturedSubmission = data_get($snapshot?->snapshot, 'review_context.submission_id');
        if ($capturedStep === null && !$submission->scope_of_work_version_id && data_get($submission->data, 'context') !== 'scope_of_work_revision') {
            $capturedStep = data_get($snapshot?->snapshot, 'relations.expertPanel.attributes.current_step');
        }
        if (($capturedStep !== null && (int) $capturedStep !== $step)
            || ($capturedSubmission !== null && (int) $capturedSubmission !== $submission->id)) $snapshot = null;
        return ['snapshot' => $snapshot, 'availability' => $snapshot ? 'available' : 'unavailable'];
    }
}
