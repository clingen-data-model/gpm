<?php

namespace Tests\Feature\End2End\Groups;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateEpNameTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('groups-manage');
        $this->expertPanel = ExpertPanel::factory()->gcep()->create();

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_update_short_or_log_base_name()
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_data_types()
    {
        $this->makeRequest([
                'long_base_name' => 'This is very long text that should more that 255 characters so that we can verify that a user cannot add a string that is too long for the database field.  I should really be surfing or walking quietly in the woods right now instead of typing this, but work.',
                'short_base_name' => 'more than sixteen',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'long_base_name' => ['The long base name may not be greater than 255 characters.'],
                'short_base_name' => ['The short base name may not be greater than 15 characters.'],
            ]);
    }

    /**
     * @test
     * @group thisone
     */
    public function validates_long_base_name_must_be_unique_for_type_if_not_null()
    {
        $existingGcep = ExpertPanel::factory()->gcep()->create(['long_base_name' => 'Early is a bad dog']);
       
        $this->makeRequest([
            'long_base_name' => null,
            'short_base_name' => 'blah',
        ])
        ->assertStatus(200);

        $this->makeRequest([
                'long_base_name' => $existingGcep->getAttributes()['long_base_name'],
                'short_base_name' => 'blah',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'long_base_name' => ['The long base name has already been taken.'],
            ]);

        $this->expertPanel->update(['long_base_name' => 'Bird']);
        $this->makeRequest([
            'long_base_name' => 'Bird',
            'short_base_name' => 'blah',
        ])
        ->assertStatus(200);
    }

    /**
     * @test
     */
    public function validates_short_base_name_must_be_unique_for_type_if_not_null()
    {
        $existingGcep = ExpertPanel::factory()->gcep()->create(['short_base_name' => 'Early']);
        $this->makeRequest([
            'long_base_name' => null,
            'short_base_name' => null,
        ])
        ->assertStatus(200);


        $this->makeRequest([
                'long_base_name' => $existingGcep->long_base_name,
                'short_base_name' => 'Early',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'short_base_name' => ['The short base name has already been taken.'],
            ]);

        $this->expertPanel->update(['short_base_name' => 'Bird']);
        $this->makeRequest([
            'short_base_name' => 'Bird',
            'long_base_name' => 'blah',
        ])
        ->assertStatus(200);
    }

    /**
     * @test
     */
    public function a_privileged_user_can_update_ep_names()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Admiral Bird'
            ])
            ->assertJsonFragment([
                'long_base_name' => 'Admiral Bird',
                'short_base_name' => 'Bird'
            ]);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'long_base_name' => 'Admiral Bird',
            'short_base_name' => 'Bird'
        ]);

        $this->assertDatabaseHas('groups', [
            'id' => $this->expertPanel->group_id,
            'name' => 'Admiral Bird'
        ]);
    }
    

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'long_base_name' => 'Admiral Bird',
            'short_base_name' => 'Bird'
        ];

        return $this->json('PUT', '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/name', $data);
    }
}
