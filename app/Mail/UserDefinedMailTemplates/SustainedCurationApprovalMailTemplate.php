<?php

namespace App\Mail\UserDefinedMailTemplates;

use App\Mail\UserDefinedMailTemplates\AbstractUserDefinedMailTemplate;

class SustainedCurationApprovalMailTemplate extends AbstractUserDefinedMailTemplate
{

    public function getTemplate(): string
    {
        return 'email.applications.approval.vcep_step_4_approval';
    }

    public function renderSubject(): string
    {
        return 'Your ClinGen expert panel '.$this->group->name.' has received final approval.';
    }

    public function getCC(): array
    {
        return [
            ['email' => 'cdwg_oversightcommittee@clinicalgenome.org', 'name' => 'CDWG Oversite Committee'],
            ['email' => 'clingentrackerhelp@unc.edu', 'name' => 'Clingen Tracker Help'],
            ['email' => 'volunteer@clinicalgenome.org', 'name' => 'CCDB Support'],
            ['email' => 'erepo@clinicalgenome.org', 'name' => 'ERepo Support'],
            ['email' => 'clingen-helpdesk@lists.stanford.edu', 'name' => 'GCI/VCI Support'],
            ['email' => 'clinvar@ncbi.nlm.nih.gov', 'name' => 'ClinVar'],
        ];
    }
    
    
}