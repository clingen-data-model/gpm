<?php

namespace Tests\Feature\End2End\Person;

use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class PersonMergeTest extends TestCase
{
    use FastRefreshDatabase;

    private $user, $group1, $group2, $person1, $person2;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->setupRoles('biocurator', 'group');
        $this->setupPermission('members-invite', 'group');
        $this->user = $this->setupUser(permissions: ['people-manage']);
        $ep1 = ExpertPanel::factory()->create();
        $ep2 = ExpertPanel::factory()->create();
        $this->group1 = $ep1->group;
        $this->group2 = $ep2->group;

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
    public function merge_gives_authority_memberships_matching_obsolete_and_deletes_obsolete_membeships()
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

    /**
     * @test
     */
    public function merge_migrates_obsoletes_cois_to_authority()
    {
        Carbon::setTestNow('2022-04-25');
        $coi = Coi::factory()->create([
            'group_member_id' => $this->person2->memberships->first()->id,
            'completed_at' => Carbon::now()
        ]);

        $this->makeRequest()
            ->assertStatus(200);

        $newMembership = $this->person1->memberships()->get()->last();

        $this->assertDatabaseHas('cois', [
            'id' => $coi->id,
            'group_member_id' => $newMembership->id,
            'completed_at' => Carbon::now()
        ]);
    }
   
    /**
     * @test
     */
    public function obsolete_user_transfered_to_unlinked_authority()
    {
        $user = User::factory()->create();
        $this->person2->update(['user_id' => $user->id]);

        $this->makeRequest()
            ->assertStatus(200);


        $this->assertDatabaseHas('people', [
            'id' => $this->person1->id,
            'user_id' => $user->id
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $this->person1->email
        ]);
    }

    /**
     * @test
     */
    public function obsolete_user_not_transfered_if_authority_has_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->person2->update(['user_id' => $user2->id]);
        $this->person1->update(['user_id' => $user1->id]);

        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('people', [
            'id' => $this->person1->id,
            'user_id' => $user1->id
        ]);

        $this->assertModelMissing($user2);

    }
    

    /**
     * @test
     */
    public function records_merge_activity()
    {
        Carbon::setTestNow('2022-04-25');
        $coi = Coi::factory()->create([
            'group_member_id' => $this->person2->memberships->first()->id,
            'completed_at' => Carbon::now()
        ]);

        $this->makeRequest()
            ->assertStatus(200);

        $expectedMessage = $this->person2->name . ' was merged into ' . $this->person1->name;

        $this->assertLoggedActivity(
            logName: 'people',
            subject: $this->person1,
            description: $expectedMessage,
            activity_type: 'person-merged',
        );
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
