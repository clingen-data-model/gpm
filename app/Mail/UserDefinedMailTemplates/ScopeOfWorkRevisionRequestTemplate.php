<?php

namespace App\Mail\UserDefinedMailTemplates;

use App\Modules\Group\Models\Submission;

class ScopeOfWorkRevisionRequestTemplate extends AbstractUserDefinedMailTemplate
{
    public function getTemplate(): string
    {
        return 'email.applications.approval.scope_of_work_revision_request';
    }

    public function renderSubject(): string
    {
        return 'Scope of Work revisions requested for '.$this->group->displayName;
    }

    public function getCC(): array
    {
        return [];
    }

    protected function getContext(): array
    {
        $context = parent::getContext();
        $submission = $this->latestScopeOfWorkSubmission();

        $context['submission'] = $submission;
        $context['targetVersion'] = data_get($submission?->data, 'target_version');
        $context['baseVersionId'] = data_get($submission?->data, 'base_version_id');
        $context['requiredRevisions'] = $this->group->comments()->pending()->requiredRevision()->get();
        $context['suggestions'] = $this->group->comments()->pending()->suggestion()->get();
        $context['judgementNotes'] = $submission ? $submission->judgements->filter(fn ($j) => ! empty($j->notes))->map(fn ($j) => $j->notes) : collect();
        return $context;
    }

    private function latestScopeOfWorkSubmission(): ?Submission
    {
        return $this->group->submissions()->with('judgements.person')->where('data->context', 'scope_of_work_revision')->latest('id')->first();
    }
}