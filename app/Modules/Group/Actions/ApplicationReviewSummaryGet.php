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
        $context = app(\App\Services\ApplicationReviewCommentContext::class);
        $submissions = $context->submissions($group);
        $empty = [];
        foreach (config('comments.types') as $key => $type) {
            $empty[str_replace('-', '_', $key)] = ['outstanding' => 0, 'resolved' => 0, 'total' => 0];
        }
        $summary = ['step1Summary' => $empty, 'step4Summary' => in_array($group->group_type_id,
            [config('groups.types.vcep.id'), config('groups.types.scvcep.id')]) ? $empty : null];
        $keys = [];
        foreach (config('comments.types') as $key => $type) $keys[$type['id']] = str_replace('-', '_', $key);
        foreach ($group->comments()->without(['creator', 'type'])->get() as $comment) {
            $step = $context->forComment($comment, $submissions)['review_step'];
            $bucket = 'step'.$step.'Summary';
            $category = $keys[$comment->comment_type_id] ?? null;
            if (!isset($summary[$bucket]) || !$category) continue;
            $summary[$bucket][$category][$comment->resolved_at ? 'resolved' : 'outstanding']++;
            $summary[$bucket][$category]['total']++;
        }
        return $summary;
    }
}
