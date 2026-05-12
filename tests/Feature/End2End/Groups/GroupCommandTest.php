<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GroupCommandTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function can_run_command_via_endpoint()
    {
        $user = $this->setupUser();
        $this->setupForGroupTest();
        $group = Group::factory()->create();

        Sanctum::actingAs($user);
        $this->json(
                'POST', 
                '/api/groups/'.$group->uuid.'/command', 
                [
                    'name' => 'Test Name!!',
                    'command' => 'tests.dummies.setGroupName'
                ]
            )
            ->assertStatus(200)
            ->assertJson([
                'id' => $group->id,
                'name' => 'Test Name!!'
            ]);
        



    }
    
}
