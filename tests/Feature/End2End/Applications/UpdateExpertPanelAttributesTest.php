<?php

namespace Tests\Feature\End2End\Applications;

use Tests\TestCase;
use App\Models\Cdwg;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
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
        $application = Application::factory()->gcep()->create();
        $data = [
            'working_name' => 'New Test Working Name',
            'long_base_name' => 'Test Expert Panel Base Name',
            'short_base_name' => 'Test EP',
            'affiliation_id' => '400001',
            'cdwg_id' => $this->cdwg->id
        ];

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('PUT', '/api/applications/'.$application->uuid, $data)
            ->assertStatus(200)
            ->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function will_not_update_other_non_ep_attributes()
    {
        $application = Application::factory()->gcep()->create();
        $data = [
            'working_name' => 'New Test Working Name',
            'long_base_name' => 'Test Expert Panel Base Name',
            'short_base_name' => 'Test EP',
            'affiliation_id' => '400001',
            'cdwg_id' => $this->cdwg->id
        ];

        $nonEpData = [
            'uuid' => Uuid::uuid4()->toString(),
            'date_initiated' => '2019-01-01',
            'date_completed' => '2019-01-01',
            'approval_dates' => json_encode(['step_1' => '2019-01-01']),
            'current_step' => 12,
            'survey_monkey_url' => 'https://bob.com'
        ];
        $dataWithUuid = array_merge($data, $nonEpData);

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('PUT', '/api/applications/'.$application->uuid, $dataWithUuid)
            ->assertStatus(200)
            ->assertJsonFragment([
                'uuid' => $application->uuid,
                'date_initiated' => $application->date_initiated->toJson(),
                'date_completed' => null,
                'current_step' => 1,
                'survey_monkey_url' => null,
                'approval_dates' => null
            ]);
    }
    
    /**
     * @test
     */
    public function validates_required_attributes()
    {

        $application = Application::factory()->gcep()->create();
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('PUT', '/api/applications/'.$application->uuid, [])
            ->assertStatus(422)
            ->assertJsonFragment([
                'working_name' => ['The working name field is required.'],
                'cdwg_id' => ['The cdwg id field is required.'],
            ]);
    }

    /**
     * @test
     */
    public function validates_data_types()
    {
        $application = Application::factory()->gcep()->create();
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('PUT', '/api/applications/'.$application->uuid, [
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
