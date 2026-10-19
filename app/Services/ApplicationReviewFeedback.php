<?php

namespace App\Services;

use App\Modules\Group\Models\Group;

class ApplicationReviewFeedback
{
    public function comments(Group $group): array
    {
        return [
            'requiredRevisions' => $group->comments()->pending()->requiredRevision()->get(),
            'suggestions' => $group->comments()->pending()->suggestion()->get(),
        ];
    }

    public function text(Group $group): string
    {
        $sections = [];
        foreach ($this->comments($group) as $key => $comments) {
            if ($comments->isEmpty()) continue;
            $heading = $key === 'requiredRevisions' ? 'Required Revisions:' : 'Suggestions:';
            $sections[] = $heading."\n".$comments->map(fn ($comment) => '- '.$comment->content)->implode("\n");
        }
        return implode("\n\n", $sections);
    }
}
