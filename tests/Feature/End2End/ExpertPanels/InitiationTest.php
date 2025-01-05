<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InitiationTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }


    /**
     * @test
     */
    public function can_create_initiate_an_application()
    {
        $data = $this->makeApplicationData();
        $data['cdwg_id'] = null;
        $response = $this->json('POST', '/api/applications', $data);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'uuid' => $data['uuid'],
            'long_base_name' => $data['working_name'],
            'date_initiated' => '2020-01-01T00:00:00.000000Z',
            'current_step' => 1
        ]);
    }

    /**
     * @test
     */
    public function validates_required_parameters_before_initiating_application()
    {
        $data = [];
        $response = $this->json('Post', '/api/applications', $data);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'uuid' => ['This is required.'],
                'working_name' => ['This is required.'],
                'expert_panel_type_id' => ['This is required.'],
            ]
        ]);
    }

    /**
     * @test
     */
    public function validates_uuid_is_a_uuid()
    {
        $data = ['uuid'=>'bob is yer uncle'];
        $response = $this->json('Post', '/api/applications', $data);

        $response->assertStatus(422);
        $response->assertJsonFragment(['uuid' => ['The uuid must be a valid UUID.']]);
    }

    /**
     * @test
     */
    public function validates_date_initiated_is_a_date()
    {
        $data = ['date_initiated'=>'bob is yer uncle'];
        $response = $this->json('Post', '/api/applications', $data);

        $response->assertStatus(422);
        $response->assertJsonFragment(['date_initiated' => ['The date initiated is not a valid date.']]);
    }
    
    /**
     * @test
     */
    public function validates_working_name_lenghth()
    {
        $data = ['working_name'=>'Aliqua anim et excepteur amet exercitation. Consequat duis fugiat qui labore laborum culpa amet. Exercitation eiusmod id velit excepteur incididunt minim magna cupidatat. Excepteur ullamco culpa ut labore exercitation laborum veniam. Cupidatat ex laborum di'];
        
        $response = $this->json('Post', '/api/applications', $data);

        $response->assertStatus(422);
        $response->assertJsonFragment(['working_name' => ['The working name may not be greater than 256 characters.']]);

        $data = ['working_name'=>'Al'];
        $response = $this->json('Post', '/api/applications', $data);

        $response->assertStatus(422);
        $response->assertJsonFragment(['working_name' => ['The working name must be at least 3 characters.']]);
    }
    
    /**
     * @test
     */
    public function validates_cdwg_id_exists()
    {
        $data = ['cdwg_id'=>'2'];
        $response = $this->json('Post', '/api/applications', $data);

        $response->assertStatus(422);
        $response->assertJsonFragment(['cdwg_id' => ['The selection is invalid.']]);
    }
    
    /**
     * @test
     */
    public function validates_expert_panel_types_id_exists()
    {
        $data = ['expert_panel_type_id'=>999];
        $response = $this->json('Post', '/api/applications', $data);

        $response->assertStatus(422);
        $response->assertJsonFragment(['expert_panel_type_id' => ['The selection is invalid.']]);
    }
}
