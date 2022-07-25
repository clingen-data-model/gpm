<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\TestResponse;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\NextActionTypesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use Database\Seeders\NextActionAssigneesTableSeeder;
use App\Modules\Group\Actions\ApplicationJudgementSubmit;

class JudgementUpdateTest extends JudgementTest
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        
        $this->judgement = $this->setupJudgement($this->expertPanel->group);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function person_other_than_approver_cannot_update_anothers_judgment()
    {
        $this->otherUser = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);
        
        Sanctum::actingAs($this->otherUser);
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function user_with_ep_manage_applications_can_update_another_users_judgement()
    {
        $this->otherUser = $this->setupUserWithPerson(permissions: ['ep-applications-manage']);
        Sanctum::actingAs($this->otherUser);

        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('judgements', [
            'id' => $this->judgement->id,
            'decision' => 'approve-after-revisions',
            'notes' => 'These are my comments I want to add.'
        ]);
    }
    
    

    /**
     * @test
     */
    public function validates_judgement_data()
    {
        $this->makeRequest([])
            ->assertValidationErrors([
                'decision' => ['This is required.']
            ]);

        $this->makeRequest(['decision' => 'hedgehogs'])
            ->assertValidationErrors([
                'decision' => ['The selection is invalid.']
            ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    private function makeRequest($data = null): TestResponse
    {
        $data = $data ?? [
            'decision' => 'approve-after-revisions',
            'notes' => "These are my comments I want to add.",
            'person_id' => $this->user->person->id
        ];

        $url = '/api/groups/'.$this->expertPanel->group->uuid.'/application/judgements/'.$this->judgement->id;
        return $this->json('PUT', $url, $data);
    }

}
