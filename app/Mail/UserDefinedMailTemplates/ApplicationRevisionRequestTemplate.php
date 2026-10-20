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

    public function reviewerNote(): string
    {
        return app(\App\Services\ApplicationReviewFeedback::class)->text($this->group, $this->group->latestSubmission);
    }

    protected function getContext(): array
    {
        $context = parent::getContext();
        $context = array_merge($context, app(\App\Services\ApplicationReviewFeedback::class)->revision($this->group, $this->group->latestSubmission));

        return $context;
    }
    
    
}
