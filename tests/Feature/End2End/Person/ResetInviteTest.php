<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Modules\Person\Models\Invite;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ResetInviteTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        // $this->seed();

        Carbon::setTestNow('2021-02-02');
        $this->invite = Invite::factory()->create(['redeemed_at' => Carbon::now()]);
    }

    #[Test]
    public function unprivileged_user_cannot_reset_invite()
    {
        $user = $this->setupUser();
        $this->actingAs($user, 'clerk');

        $this->makeRequest()
            ->assertStatus(403);
    }

    #[Test]
    public function privileged_user_can_reset_invite()
    {
        $user = $this->setupUser(null, ['people-manage']);
        $this->actingAs($user, 'clerk');

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
