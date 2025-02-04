<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Modules\Person\Models\Invite;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class ResetInviteTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        // $this->seed();

        Carbon::setTestNow('2021-02-02');
        $this->invite = Invite::factory()->create(['redeemed_at' => Carbon::now()]);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_reset_invite()
    {
        $user = $this->setupUser();
        Sanctum::actingAs($user);

        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function privileged_user_can_reset_invite()
    {
        $user = $this->setupUser(null, ['people-manage']);
        Sanctum::actingAs($user);

        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->invite->id,
                'redeemed_at' => null
            ]);

        $this->assertDatabaseHas('invites', [
            'id' => $this->invite->id,
            'redeemed_at' => null
        ]);
    }
    
    
    
    private function makeRequest()
    {
        return $this->json('PUT', '/api/people/invites/'.$this->invite->code.'/reset');
    }
}
