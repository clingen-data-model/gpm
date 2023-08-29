<?php

namespace App\Mail\UserDefinedMailTemplates;

use Illuminate\Support\Facades\View;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Mail\UserDefinedMailTemplates\UserDefinedTemplate;
use App\Mail\UserDefinedMailTemplates\AbstractUserDefinedMailTemplate;
class InitialApprovalMailTemplate extends AbstractUserDefinedMailTemplate
{

    public function getTemplate(): string
    {
        return 'email.applications.approval.initial_approval';
    }

    public function renderSubject(): string
    {
        return 'Scope and Membership application for your ClinGen expert panel '
                . $this->group->name
                . ' has been approved.';
    }

    public function getCC(): array
    {
        return [
            ['address' => 'cdwg_oversightcommittee@clinicalgenome.org', 'name' => 'CDWG Oversite Committee'],
            ['address' => 'clingentrackerhelp@unc.edu', 'name' => 'Clingen Tracker Help'],
            ['address' => 'volunteer@clinicalgenome.org', 'name' => 'CCDB Support'],
            ['address' => 'erepo@clinicalgenome.org', 'name' => 'ERepo Support'],
            ['address' => 'clingen-helpdesk@lists.stanford.edu', 'name' => 'GCI/VCI Support'],
            ['address' => 'brl-cspec@bcm.edu', 'name' => 'CSpec Support'],
        ];
    }


}
