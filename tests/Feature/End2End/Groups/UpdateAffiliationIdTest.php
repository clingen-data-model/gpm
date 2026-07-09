<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use App\Modules\Group\Models\Group;

class UpdateAffiliationIdTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->user = $this->setupUser(permissions: ['groups-manage']);
        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function unprivileged_user_cannot_update_affiliation_id()
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    #[Test]
    public function privileged_user_can_set_affiliation_id()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->expertPanel->group->id,
                'affiliation_id' => '49999'
            ]);

        $this->assertDatabaseHas('groups', [
            'id' => $this->expertPanel->group->id,
            'affiliation_id' => '49999'
        ]);
    }

    #[Test]
    public function validates_affiliation_id_format()
    {
        $this->makeRequest('400000')
            ->assertStatus(422)
            ->assertJsonFragment([
                'affiliation_id' => ['The affiliation id must be 5 digits.']
            ]);

        $this->makeRequest('59999')
            ->assertStatus(422)
            ->assertJsonFragment([
                'affiliation_id' => ['GCEP Affiliation IDs must start with "4".']
            ]);

        $this->expertPanel->group->update(['group_type_id' => config('groups.types.vcep.id')]);
        $this->makeRequest('49999')
            ->assertStatus(422)
            ->assertJsonFragment([
                'affiliation_id' => ['VCEP and SC-VCEP Affiliation IDs must start with "5".']
            ]);
    }

    #[Test]
    public function validates_that_affiliation_id_is_not_already_in_use()
    {
        $otherEp = ExpertPanel::factory()->gcep()->create();
        $otherEp->group->update([
            'affiliation_id' => '40666',
        ]);

        $this->makeRequest('40666')
            ->assertStatus(422)
            ->assertJsonFragment([
                'affiliation_id' => ['The affiliation id has already been taken.']
            ]);
    }

    #[Test]
    public function records_affiliation_id_updated()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertLoggedActivity(
            subject: $this->expertPanel->group,
            description: 'Affiliation ID set to 49999.',
            properties: ['affiliation_id' => '49999'],
            logName: 'groups'
        );
    }

    private function makeRequest($affiliationId = null)
    {
        $affiliationId = $affiliationId ?? '49999';
        return $this->json(
            'put',
            '/api/groups/'.$this->expertPanel->group->uuid.'/affiliation-id',
            ['affiliation_id' => $affiliationId]
        );
    }

    #[Test]
    public function non_super_user_cannot_update_working_group_affiliation_id(): void
    {
        $group = Group::factory()->wg()->create([
            'affiliation_id' => '60001',
        ]);

        $this->makeRequestForGroup($group, '60002')
            ->assertStatus(422);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'affiliation_id' => '60001',
        ]);
    }

    #[Test]
    public function super_user_can_update_working_group_affiliation_id(): void
    {
        $group = Group::factory()->wg()->create([
            'affiliation_id' => '60001',
        ]);

        $superUser = $this->setupUser();
        $superUser->assignRole('super-admin');
        Sanctum::actingAs($superUser);

        $this->json('put', '/api/groups/'.$group->uuid.'/affiliation-id', [
            'affiliation_id' => '60002',
        ])->assertStatus(200);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'affiliation_id' => '60002',
        ]);
    }
    private function makeRequestForGroup(Group $group, $affiliationId)
    {
        return $this->json(
            'put',
            '/api/groups/'.$group->uuid.'/affiliation-id',
            ['affiliation_id' => $affiliationId]
        );
    }
}
