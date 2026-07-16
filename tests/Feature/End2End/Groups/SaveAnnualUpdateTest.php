<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use App\Models\AnnualUpdate;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\AnnualUpdateWindowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class SaveAnnualUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->runSeeder(AnnualUpdateWindowSeeder::class);

        $this->user = $this->setupUser(permissions: ['annual-updates-manage']);
        $this->expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->coordinator = GroupMember::factory()
                                ->create(['group_id' => $this->expertPanel->group->id])
                                ->assignRole('coordinator');

        $this->annualReview = AnnualUpdate::create(['expert_panel_id' => $this->expertPanel->id]);
        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function unprivileged_user_cannot_save_annual_update()
    {
        $this->user->revokePermissionTo('annual-updates-manage');

        $this->makeRequest()
            ->assertStatus(403);
    }

    #[Test]
    public function saves_data_submitted_by_privileged_user()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertAnnualUpdateHasData($this->makeRequestData()['data']);
    }

    #[Test]
    public function does_not_save_data_field_not_in_list()
    {
        $expectedData = $this->makeRequestData();

        $submitData = $expectedData;
        $submitData['data']['farts'] = 'yes';
        $this->makeRequest($submitData)
            ->assertStatus(200);

        $this->assertAnnualUpdateHasData($expectedData['data']);

        $this->assertDatabaseMissing('annual_updates', [
            'id' => $this->annualReview->id,
            'expert_panel_id' => $this->expertPanel->id,
            'submitter_id' => $this->coordinator->id,
            'data->farts' => 'yes',
        ]);
    }
    
    // The saved blob is padded out with every known field, so assert the
    // submitted values individually rather than matching the whole JSON.
    private function assertAnnualUpdateHasData(array $data)
    {
        foreach ($data as $key => $value) {
            $this->assertDatabaseHas('annual_updates', [
                'id' => $this->annualReview->id,
                'expert_panel_id' => $this->expertPanel->id,
                'submitter_id' => $this->coordinator->id,
                'data->'.$key => $value,
            ]);
        }
    }

    private function makeRequest($data = null)
    {
        $data = $data ?? $this->makeRequestData();

        $url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/annual-updates/'.$this->annualReview->id;
        return $this->json('PUT', $url, $data);
    }

    private function makeRequestData()
    {
        return [
            'expert_panel_id' => $this->expertPanel->id,
            'submitter_id' => $this->coordinator->id,
            'data' => [
                'grant' => 'UNC',
                'gci_use' => 'yes',
                'published_count' => 10
            ]
        ];
    }
}
