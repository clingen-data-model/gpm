<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JudgementDeleteTest extends JudgementTest
{
    public function setup():void
    {
        parent::setup();
        $this->judgement = $this->setupJudgement($this->expertPanel->group);
    }

    /**
     * @test
     */
    public function person_other_than_approver_cannot_delete_anothers_judgment()
    {
        $this->otherUser = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);
        
        Sanctum::actingAs($this->otherUser);
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function user_with_ep_manage_applications_can_delete_another_users_judgement()
    {
        $this->otherUser = $this->setupUserWithPerson(permissions: ['ep-applications-manage']);
        Sanctum::actingAs($this->otherUser);

        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseMissing('judgements', [
            'id' => $this->judgement->id,
        ]);
    }

    /**
     * @test
     */
    public function user_who_made_judgement_can_delete_it()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseMissing('judgements', [
            'id' => $this->judgement->id,
        ]);
    }
    

    private function makeRequest($data = null): TestResponse
    {
        $url = '/api/groups/'.$this->expertPanel->group->uuid.'/application/judgements/'.$this->judgement->id;
        return $this->json('delete', $url);
    }
    
}
