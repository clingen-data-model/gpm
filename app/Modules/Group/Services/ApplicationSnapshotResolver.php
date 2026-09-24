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
            return ['snapshot' => $snapshot, 'availability' => $snapshot ? 'available' : 'unavailable'];
        }
        $snapshots = $query->limit(2)->get();
        return [
            'snapshot' => $snapshots->count() === 1 ? $snapshots->first() : null,
            'availability' => match ($snapshots->count()) {
                0 => 'unavailable', 1 => 'available', default => 'ambiguous',
            },
        ];
    }
}
