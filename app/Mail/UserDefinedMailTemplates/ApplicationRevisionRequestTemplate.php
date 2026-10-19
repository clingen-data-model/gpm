<?php

namespace App\Mail\UserDefinedMailTemplates;

use App\Mail\UserDefinedMailTemplates\AbstractUserDefinedMailTemplate;

class ApplicationRevisionRequestTemplate extends AbstractUserDefinedMailTemplate
{

    public function getTemplate(): string
    {
        return 'email.applications.approval.revision_request';
    }

    public function renderSubject(): string
    {
        return $this->group->type->display_name.' application revisions requested.';
    }

    public function getCC(): array
    {
        return [
        ];
    }

    protected function getContext(): array
    {
        $context = parent::getContext();
        $context = array_merge($context, app(\App\Services\ApplicationReviewFeedback::class)->comments($this->group));
        $context['judgementNotes'] = $this->group
                                        ->latestSubmission
                                        ->judgements
                                        ->filter(fn ($j) => $j->notes !== null)
                                        ->map(fn ($j) => $j->notes);

        return $context;
    }
    
    
}
