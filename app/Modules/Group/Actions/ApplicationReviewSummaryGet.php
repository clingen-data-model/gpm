<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Services\ApplicationReviewCommentAccess;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class ApplicationReviewSummaryGet
{
    use AsController;

    public function asController(ActionRequest $request, Group $group): array
    {
        app(ApplicationReviewCommentAccess::class)->authorize($request->user(), $group);
        $counts = $group->comments()->selectRaw('comment_type_id, SUM(CASE WHEN resolved_at IS NULL THEN 1 ELSE 0 END) AS outstanding, SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) AS resolved')
            ->groupBy('comment_type_id')->get()->keyBy('comment_type_id');

        $summary = [];
        foreach (config('comments.types') as $key => $type) {
            $count = $counts->get($type['id']);
            $outstanding = (int) ($count?->outstanding ?? 0);
            $resolved = (int) ($count?->resolved ?? 0);
            $summary[str_replace('-', '_', $key)] = compact('outstanding', 'resolved') + ['total' => $outstanding + $resolved];
        }
        return $summary;
    }
}
