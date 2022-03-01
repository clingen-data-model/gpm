<?php

namespace Tests\Feature\End2End\Groups;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Database\Seeders\CurationReviewProtocolsSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateCurationProcessTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupPermission('ep-applications-manage');
        $this->setupForGroupTest();
        $this->runSeeder(CurationReviewProtocolsSeeder::class);

        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);
        $this->expertPanel = ExpertPanel::factory()->vcep()->create();

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_update_curationReviewProtocol()
    {
        $this->user->revokePermissionTo('ep-applications-manage');

        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_required_data()
    {
        $this->makeRequest(['expert_panel_type_id' => $this->expertPanel->expert_panel_type_id])
            ->assertStatus(422)
            ->assertJsonFragment([
                'curation_review_protocol_id' => ['This is required.'],
                'meeting_frequency' => ['This is required.'],
            ]);

        $this->makeRequest([
                'curation_review_protocol_id' => config('expert_panels.curation_protocols.other.id')
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'curation_review_protocol_other' => ['This is required.'],
            ]);
    }

    /**
     * @test
     */
    public function validates_data_formats()
    {
        $this->makeRequest([
                'curation_review_protocol_id' => 8000,
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'curation_review_protocol_id' => ['The selection is invalid.']
            ]);
    }

    /**
     * @test
     */
    public function priveleged_user_can_update_curation_protocol_information()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'curation_review_process_notes' => 'These are some notes.'
            ]);
    
        $this->assertDatabaseHas('expert_panels', [
            'curation_review_protocol_id' => config('expert_panels.curation_protocols.single-biocurator.id'),
            'meeting_frequency' => '2 times a month.',
            'curation_review_process_notes' => 'These are some notes.',
            'id' => $this->expertPanel->id,
            'long_base_name' => $this->expertPanel->long_base_name
        ]);
    }
    
    
    

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'curation_review_protocol_id' => config('expert_panels.curation_protocols.single-biocurator.id'),
            'meeting_frequency' => '2 times a month.',
            'curation_review_process_notes' => 'These are some notes.',
            'long_base_name' => 'I should not get updated.'
        ];
        $url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/curation-review-protocols';
        return $this->json('put', $url, $data);
    }
}
