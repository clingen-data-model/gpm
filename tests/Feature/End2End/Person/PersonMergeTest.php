<?php

namespace Tests\Feature\End2End\Person;

use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonMergeTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->setupRoles('biocurator', 'group');
        $this->setupPermission('members-invite', 'group');
        $this->user = $this->setupUser(permissions: ['people-manage']);
        $this->group1 = Group::factory()->create();
        $this->group2 = Group::factory()->create();

        $this->person1 = $this->setupPerson($this->group1);
        $this->person2 = $this->setupPerson($this->group2);
        
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_delete_person()
    {
        $this->user->revokePermissionTo('people-manage');

        $this->makeRequest()->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_params()
    {
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'authority_id' => ['This is required.'],
                    'obsolete_id' => ['This is required.']
                ]
            ]);

        $this->makeRequest(['authority_id' => 555, 'obsolete_id' => 666])
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'authority_id' => ['The selection is invalid.'],
                    'obsolete_id' => ['The selection is invalid.']
                ]
            ]);

        $this->makeRequest([
            'authority_id' => $this->person1->id,
            'obsolete_id' => $this->person1->id,
        ])
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'authority_id' => ['The authority id and obsolete id must be different.'],
                ]
            ]);
    }
    

    /**
     * @test
     */
    public function permissioned_user_can_delete_person_and_all_relations()
    {
        Carbon::setTestNow('2022-03-15');
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertSoftDeleted('people', ['id' => $this->person2->id, 'deleted_at' => Carbon::now()]);
        $this->assertDatabaseHas('invites', ['person_id' => $this->person2->id, 'deleted_at' => Carbon::now()]);
        $this->assertDatabaseHas(
            'group_members',
            [
                'person_id' => $this->person2->id,
                'group_id' => $this->group2->id,
                'deleted_at' => Carbon::now()
            ]
        );
        $this->assertDatabaseHas(
            'group_members',
            [
                'person_id' => $this->person1->id,
                'group_id' => $this->group2->id,
                'deleted_at' => null
            ]
        );
        $this->assertDatabaseMissing('users', ['id' => $this->person2->user_id]);

        $this->assertEquals('biocurator', $this->person1->memberships()->get()->last()->roles->first()->name);
        $this->assertEquals('members-invite', $this->person1->memberships()->get()->last()->permissions->first()->name);
    }
    

    private function makeRequest($data = null)
    {
        $data = $data ?? ['authority_id' => $this->person1->id, 'obsolete_id' => $this->person2->id];
        return $this->json('put', '/api/people/merge', $data);
    }

    private function setupPerson(Group $group)
    {
        $person = Person::factory()->create();
        $person->user()->associate(User::factory()->make());
        $person->invite()->save(Invite::factory()->make());
        $membership = app()->make(MemberAdd::class)->handle($group, $person);
        $membership->assignRole('biocurator');
        $membership->givePermissionTo('members-invite');
        return $person;
    }
}
