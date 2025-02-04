<?php

namespace Tests\Feature\End2End\Mail;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use App\Mail\UserDefinedMailTemplates\InitialApprovalMailTemplate;
use App\Mail\UserDefinedMailTemplates\SpecificationDraftMailTemplate;
use App\Mail\UserDefinedMailTemplates\SpecificationPilotMailTemplate;
use App\Mail\UserDefinedMailTemplates\SustainedCurationApprovalMailTemplate;
use App\Modules\ExpertPanel\Notifications\ApplicationStepApprovedNotification;

class RenderUserDefinedTemplateTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function returns_data_for_step_1_approved()
    {
        $response = $this->makeRequest();
        $response->assertStatus(200);
        $response->assertJson([
                'subject' => 'Scope and Membership application for your ClinGen expert panel '.$this->expertPanel->group->name.' has been approved.',
                'cc' => [
                    ['address' => 'cdwg_oversightcommittee@clinicalgenome.org', 'name' => 'CDWG Oversite Committee'],
                    ['address' => 'clingentrackerhelp@unc.edu', 'name' => 'Clingen Tracker Help'],
                    ['address' => 'volunteer@clinicalgenome.org', 'name' => 'CCDB Support'],
                    ['address' => 'erepo@clinicalgenome.org', 'name' => 'ERepo Support'],
                    ['address' => 'clingen-helpdesk@lists.stanford.edu', 'name' => 'GCI/VCI Support'],
                ],
                'to' => $this->expectedTo()
            ]);
    }

    /**
     * @test
     */
    public function renders_draft_email()
    {
        $response = $this->makeRequest(null, SpecificationDraftMailTemplate::class);
        $response->assertStatus(200);
        $response->assertJson([
                'subject' => 'Draft specification for your ClinGen expert panel '.$this->expertPanel->group->name.' has been approved.',
                'cc' => null,
                'to' => $this->expectedTo()
            ]);
    }
    
    /**
     * @test
     */
    public function renders_pilot_email()
    {
        $response = $this->makeRequest(null, SpecificationPilotMailTemplate::class);
        $response->assertStatus(200);
        $response->assertJson([
                'subject' => 'Specification pilot for your ClinGen expert panel '.$this->expertPanel->group->name.' has been approved.',
                'cc' => null,
                'to' => $this->expectedTo()
            ]);
    }
    
    /**
     * @test
     */
    public function renders_sustained_curation_email()
    {
        $response = $this->makeRequest(null, SustainedCurationApprovalMailTemplate::class);
        $response->assertStatus(200);
        $response->assertJson([
                'subject' => 'Your ClinGen expert panel '.$this->expertPanel->group->name.' has received final approval.',
                'cc' => [
                    ['address' => 'cdwg_oversightcommittee@clinicalgenome.org', 'name' => 'CDWG Oversite Committee'],
                    ['address' => 'clingentrackerhelp@unc.edu', 'name' => 'Clingen Tracker Help'],
                    ['address' => 'volunteer@clinicalgenome.org', 'name' => 'CCDB Support'],
                    ['address' => 'erepo@clinicalgenome.org', 'name' => 'ERepo Support'],
                    ['address' => 'clingen-helpdesk@lists.stanford.edu', 'name' => 'GCI/VCI Support'],
                    ['address' => 'clinvar@ncbi.nlm.nih.gov', 'name' => 'ClinVar'],
                ],
                'to' => $this->expectedTo()
            ]);
    }
    
    
    private function makeRequest($data = null, $templateClass = null)
    {
        $templateClass = $templateClass ?? InitialApprovalMailTemplate::class;
        $data = $data ?? [
            'templateClass' => $templateClass,
        ];

        return $this->json('GET', '/api/email-drafts/groups/'.$this->expertPanel->group->uuid, $data);
    }

    private function expectedTo()
    {
        return $this->expertPanel->fresh()->group->contacts
            ->pluck('person')
            ->map(function ($c) {
                return [
                    'name' => $c->name,
                    'email' => $c->email,
                    'uuid' => $c->uuid
                ];
            })
            ->toArray();
    }
}
