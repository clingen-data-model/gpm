<?php

namespace App\Services;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Submission;

class ApplicationReviewFeedback
{
    public function comments(Group $group): array
    {
        return [
            'requiredRevisions' => $group->comments()->pending()->requiredRevision()->get(),
            'suggestions' => $group->comments()->pending()->suggestion()->get(),
        ];
    }

    public function revision(Group $group, ?Submission $submission): array
    {
        return array_merge($this->comments($group), [
            'judgementNotes' => $submission?->judgements->filter(fn ($j) => filled($j->notes))->pluck('notes') ?? collect(),
        ]);
    }

    public function text(Group $group, ?Submission $submission = null): string
    {
        $sections = [];
        foreach ($this->revision($group, $submission) as $key => $comments) {
            if ($comments->isEmpty()) continue;
            $heading = match ($key) { 'requiredRevisions' => 'Required Revisions:', 'suggestions' => 'Suggestions:', default => 'Feedback from chairs:' };
            $sections[] = $heading."\n".$comments->map(fn ($comment) => '- '.(is_string($comment) ? $comment : $comment->content))->implode("\n");
        }
        return implode("\n\n", $sections);
    }
}
