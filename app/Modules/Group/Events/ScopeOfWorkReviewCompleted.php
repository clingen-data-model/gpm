<?php

namespace App\Modules\Group\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Models\ScopeOfWorkVersion;

class ScopeOfWorkReviewCompleted extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    public function __construct(
        public Submission $submission,
        public ScopeOfWorkVersion $revision,
        public string $outcome
    ) {
        $this->group = $submission->group;
    }

    public function getProperties(): array
    {
        return [
            'submission_id' => $this->submission->id,
            'scope_of_work_version_id' => $this->revision->id,
            'outcome' => $this->outcome,
        ];
    }

    public function getLogEntry(): string
    {
        return match ($this->outcome) {
            'approved' => 'Scope of Work revision ' . $this->revision->version_label . ' was approved.',
            'revisions_requested' => 'Revisions were requested for Scope of Work revision ' . $this->revision->version_label . '.',
            default => 'Scope of Work revision ' . $this->revision->version_label . ' review completed: ' . $this->outcome . '.',
        };
    }

    public function shouldPublish(): bool
    {
        return false;
    }
}