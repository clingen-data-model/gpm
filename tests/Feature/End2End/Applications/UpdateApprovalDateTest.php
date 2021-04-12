<?php

namespace Tests\Feature\End2End\Applications;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UpdateApprovalDateTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
        $this->application = Application::factory()
                                ->create([
                                    'ep_type_id' => 2, 
                                    'approval_dates'=>['step 1' => '2020-01-01']
                                ]);
    }
    

    /**
     * @test
     *
     */
    public function it_validates_request_params()
    {
        Sanctum::actingAs($this->user);
        $this->json('put', '/api/applications/'.$this->application->uuid.'/approve', [])
            ->assertStatus(422)
            ->assertJson(['errors' => [
                'step' => ['The step field is required.'],
                'date_approved' => ['The date approved field is required.']
                ]
            ]);

            $this->json('put', '/api/applications/'.$this->application->uuid.'/approve', [
                'step' => '10',
                'date_approved' => 'beans'
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['errors' => [
                'step' => ['The step may not be greater than 4.'],
                'date_approved' => ['The date approved is not a valid date.']
            ]]);
    }

    /**
     * @test
     */
    public function it_stores_new_date_and_returns_the_document()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json('put', '/api/applications/'.$this->application->uuid.'/approve', [
            'step' => 1,
            'date_approved' => '2021-04-22T04:00:00.000000Z'
        ]);
        $response->assertStatus(200);

        $this->assertEquals(['step 1' => '2021-04-22T04:00:00.000000Z'], $response->original['approval_dates']);

        $this->assertDatabaseHas('applications', [
            'uuid' => $this->application->uuid,
            'approval_dates' => json_encode(['step 1' => '2021-04-22T04:00:00.000000Z'])
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->application->id,
            'description' => 'Approval date updated to 2021-04-22T04:00:00.000000Z for step 1'
        ]);
    }
    
}