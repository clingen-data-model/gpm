<?php

namespace App\Mail\UserDefinedMailTemplates;

use App\Mail\UserDefinedMailTemplates\AbstractUserDefinedMailTemplate;

class SpecificationPilotMailTemplate extends AbstractUserDefinedMailTemplate
{

    public function getTemplate():string
    {
        return 'email.applications.approval.vcep_step_3_approval';
    }
    
    public function renderSubject(): string
    {
        return 'Specification pilot for your ClinGen expert panel '
                .$this->group->name
                .' has been approved.';       
    }
}