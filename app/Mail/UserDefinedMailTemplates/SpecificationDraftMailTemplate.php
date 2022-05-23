<?php

namespace App\Mail\UserDefinedMailTemplates;

use App\Mail\UserDefinedMailTemplates\AbstractUserDefinedMailTemplate;

class SpecificationDraftMailTemplate extends AbstractUserDefinedMailTemplate
{

    public function getTemplate():string
    {
        return 'email.applications.approval.vcep_step_2_approval';
    }

    public function renderSubject(): string
    {
        return 'Draft specification for your ClinGen expert panel '
                .$this->group->name
                .' has been approved.';       
    }
}