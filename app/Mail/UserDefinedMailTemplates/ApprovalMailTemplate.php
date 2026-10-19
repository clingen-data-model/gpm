<?php

namespace App\Mail\UserDefinedMailTemplates;

use App\Services\ApplicationReviewFeedback;

abstract class ApprovalMailTemplate extends AbstractUserDefinedMailTemplate
{
    protected function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'approvalFeedback' => app(ApplicationReviewFeedback::class)->comments($this->group),
        ]);
    }
}
