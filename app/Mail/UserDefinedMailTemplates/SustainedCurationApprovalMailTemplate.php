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
            ['address' => 'cdwg_oversightcommittee@clinicalgenome.org', 'name' => 'CDWG Oversite Committee'],
            ['address' => 'clingentrackerhelp@unc.edu', 'name' => 'Clingen Tracker Help'],
            ['address' => 'volunteer@clinicalgenome.org', 'name' => 'CCDB Support'],
            ['address' => 'erepo@clinicalgenome.org', 'name' => 'ERepo Support'],
            ['address' => 'clingen-helpdesk@lists.stanford.edu', 'name' => 'GCI/VCI Support'],
            ['address' => 'clinvar@ncbi.nlm.nih.gov', 'name' => 'ClinVar'],
            ['address' => 'brl-cspec@bcm.edu', 'name' => 'CSpec Support'],
        ];
    }


}
