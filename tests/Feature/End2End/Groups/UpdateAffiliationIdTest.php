<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class UpdateAffiliationIdTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->user = $this->setupUser(permissions: ['groups-manage']);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_update_affiliation_id()
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function privileged_user_can_set_affiliation_id()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->expertPanel->id,
                'affiliation_id' => '49999'
            ]);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'affiliation_id' => '49999'
        ]);
    }

    /**
     * @test
     */
    public function validates_affiliation_id_format()
    {
        $this->makeRequest('400000')
            ->assertStatus(422)
            ->assertJsonFragment([
                'affiliation_id' => ['The affiliation id must be 5 characters.']
            ]);

        $this->makeRequest('59999')
            ->assertStatus(422)
            ->assertJsonFragment([
                'affiliation_id' => ['GCEP affiliation IDs must start with "4"']
            ]);

        $this->expertPanel->group->update(['group_type_id' => config('groups.types.vcep.id')]);
        $this->makeRequest('49999')
            ->assertStatus(422)
            ->assertJsonFragment([
                'affiliation_id' => ['VCEP affiliation IDs must start with "5"']
            ]);
    }

    /**
     * @test
     */
    public function validates_that_affiliation_id_is_not_already_in_use()
    {
        $otherEp = ExpertPanel::factory()->gcep()->create(['affiliation_id' => '40666']);

        $this->makeRequest('40666')
            ->assertStatus(422)
            ->assertJsonFragment([
                'affiliation_id' => ['The affiliation id has already been taken.']
            ]);
    }

    /**
     * @test
     */
    public function records_affiliation_id_updated()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertLoggedActivity(
            subject: $this->expertPanel->group,
            description: 'EP affiliation_id set to 49999.',
            properties: ['affiliation_id' => '49999'],
            logName: 'groups'
        );
    }



    private function makeRequest($affiliationId = null)
    {
        $affiliationId = $affiliationId ?? '49999';
        return $this->json(
            'put',
            '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/affiliation-id',
            ['affiliation_id' => $affiliationId]
        );
    }
}
