<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Tests\TestCase;
use App\Models\Cdwg;
use App\Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateExpertPanelAttributesTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
        $this->cdwg = Cdwg::factory()->create();

        $this->longText = "Sint nisi commodo nisi tempor adipisicing. Velit officia exercitation voluptate anim consequat eiusmod nisi officia consequat duis aute enim. Eu cupidatat nostrud dolore esse exercitation tempor anim magna ex eiusmod incididunt pariatur. Laboris est eu aup";
    }

    /**
     * @test
     */
    public function sets_ep_attributes()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create();
        $data = [
            'working_name' => 'farts',
            'long_base_name' => 'Test Expert Panel Base Name GCEP',
            'short_base_name' => 'Test EP GCEP',
            'affiliation_id' => '400001',
            'cdwg_id' => $this->cdwg->id,
        ];


        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('PUT', '/api/applications/'.$expertPanel->uuid, $data);
        $response->assertStatus(200);
        $response->assertJsonFragment([

        ]);
    }

    /**
     * @test
     */
    public function will_not_update_other_non_ep_attributes()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create();
        $data = [
            'working_name' => 'test test teset',
            'long_base_name' => 'Test Expert Panel Base Name',
            'short_base_name' => 'Test EP',
            'affiliation_id' => '400001',
            'cdwg_id' => $this->cdwg->id,
            'group' => [
                'name' => 'New Test Working Name',
            ]
        ];

        $nonEpData = [
            'uuid' => Uuid::uuid4()->toString(),
            'date_initiated' => '2019-01-01',
            'date_completed' => '2019-01-01',
            'step_1_approval_date' => '2019-01-01 00:00:00',
            'current_step' => 12,
            'coi_code' => '123456789012'
        ];
        $dataWithUuid = array_merge($data, $nonEpData);

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('PUT', '/api/applications/'.$expertPanel->uuid, $dataWithUuid)
            ->assertStatus(200)
            ->assertJsonFragment([
                'uuid' => $expertPanel->uuid,
                'date_initiated' => $expertPanel->date_initiated->toJson(),
                'date_completed' => null,
                'current_step' => 1,
                'coi_code' => $expertPanel->coi_code,
                'step_1_approval_date' => null
            ]);
    }
    
    /**
     * @test
     */
    public function validates_required_attributes()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create();
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('PUT', '/api/applications/'.$expertPanel->uuid, [])
            ->assertStatus(422)
            ->assertJsonFragment([
                'working_name' => ['The working name field is required.'],
            ]);
    }

    /**
     * @test
     */
    public function validates_data_types()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create();
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('PUT', '/api/applications/'.$expertPanel->uuid, [
                'working_name' => $this->longText,
                'cdwg_id' => 999,
                'long_base_name' => $this->longText,
                'short_base_name' => 'more than sixteen',
                'affiliation_id' => '4000000001',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'working_name' => ['The working name may not be greater than 255 characters.'],
                'cdwg_id' => ['The selected cdwg id is invalid.'],
                'long_base_name' => ['The long base name may not be greater than 255 characters.'],
                'short_base_name' => ['The short base name may not be greater than 15 characters.'],
                'affiliation_id' => ['The affiliation id may not be greater than 8 characters.'],
            ]);
    }
}
