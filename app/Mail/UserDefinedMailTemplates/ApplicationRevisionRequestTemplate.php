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
        return $this->group->type->name.' appliation revisions requested.';
    }

    public function getCC(): array
    {
        return [
        ];
    }
    
    
}