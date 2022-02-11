<?php

namespace Tests\Feature\End2End\Groups\Members;

use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Models\Role as ModelsRole;
use Spatie\Permission\Models\Role;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Notifications\AddedToGroupNotification;

/**
 * @group groups
 * @group members
 */
class AddGroupMemberTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpGroupPersonAndMember;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->group = Group::factory()->create(['group_type_id'=> 1]);
        $this->person = Person::factory()->create();
        $this->user = $this->setupUser(permissions: ['groups-manage']);
        Sanctum::actingAs($this->user);

        $this->admin = User::factory()->create();
        $this->role = config('permission.models.role')::factory()
                        ->create(['scope' => 'group']);
    }

    /**
     * @test
     */
    public function returns_404_if_group_not_found()
    {
        $data = $data ?? [
            'person_id' => $this->person->id,
            'role_id' => $this->role->id,
        ];

        $url = '/api/groups/'.'blahblahblah'.'/members';

        $response = $this->json('POST', $url, $data)->assertStatus(404);
    }
    
    /**
     * @test
     */
    public function unprivileged_user_cannot_add_a_group_member()
    {
        $this->user->revokePermissionTo('groups-manage');

        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function user_with_epApplicationsManage_permission_can_add_group_member()
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->user->givePermissionTo('ep-applications-manage');
        
        $this->makeRequest()
            ->assertStatus(201);
    }
    
    /**
     * @test
     */
    public function user_with_annualReviewsManage_permission_can_add_group_member()
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->user->givePermissionTo('annual-updates-manage');
        $this->makeRequest()
            ->assertStatus(201);
    }
    

    /**
     * @test
     */
    public function user_with_with_group_inviteMembers_permission_can_add_group_member()
    {
        $this->user->revokePermissionTo('groups-manage');
        $groupMember = GroupMember::factory()->create(['group_id' => $this->group->id]);
        $groupMember->givePermissionTo('members-invite');
        $groupMember->person->update(['user_id' => $this->user->id]);

        $this->makeRequest()
            ->assertStatus(201);
    }
    

    /**
     * @test
     */
    public function can_add_member_to_expert_panel()
    {
        $response = $this->makeRequest();
        $response->assertStatus(201);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'person_id' => $this->person->id
        ]);
    }
    
    /**
     * @test
     */
    public function returns_new_member_with_person_roles_and_start_date()
    {
        Carbon::setTestNow('2021-10-07');
        $response = $this->makeRequest();
        $response->assertStatus(201);
        $response->assertJsonFragment([
            'person_id' => $this->person->id,
            'group_id' => $this->group->id,
        ]);
        $response->assertJsonFragment([
            'uuid' => $this->person->uuid,
            'first_name' => $this->person->first_name,
            'start_date' => Carbon::now()->toISOString()
        ]);
        $this->assertEquals($this->role->id, $response->original->roles[0]->id);
    }
    
    /**
     * @test
     */
    public function adds_group_roles_to_new_member()
    {
        $role2 = ModelsRole::factory()->create(['scope' => 'group']);

        $response = $this->makeRequest(personId: null, roleIds: [$this->role->id, $role2->id]);
        $response->assertStatus(201);

        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => GroupMember::class,
            'model_id' => $response->original->id,
            'role_id' => $this->role->id
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => GroupMember::class,
            'model_id' => $response->original->id,
            'role_id' => $role2->id
        ]);
    }

    /**
     * @test
     */
    public function fires_MemberAdded_event()
    {
        Event::fake();
        $this->makeRequest()
            ->assertStatus(201);
        Event::assertDispatched(MemberAdded::class, function ($event) {
            return $event->groupMember->person_id == $this->person->id
                && $event->groupMember->group_id == $this->group->id;
        });
    }

    /**
     * @test
     */
    public function member_added_activity_logged()
    {
        $this->makeRequest();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Group::class,
            'subject_id' => $this->group->id,
            'properties->email' => $this->person->email
        ]);
    }
    
    /**
     * @test
     */
    public function saves_is_contact_attribute()
    {
        $this->makeRequest(isContact: true);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'person_id' => $this->person->id,
            'is_contact' => 1
        ]);
    }
    
    /**
     * @test
     */
    public function person_is_notified_of_being_added_to_group()
    {
        Notification::fake(AddedToGroupNotificaiton::class);
        $this->makeRequest()
            ->assertStatus(201);

        Notification::assertSentTo($this->person, AddedToGroupNotification::class);
    }
    

    private function makeRequest($personId = null, ?array $roleIds = null, bool $isContact = false)
    {
        $data = $data ?? [
            'person_id' => $personId ?? $this->person->id,
            'role_ids' => $roleIds ?? [$this->role->id],
            'is_contact' => $isContact
        ];

        $url = '/api/groups/'.$this->group->uuid.'/members';

        $response = $this->json(
            'POST',
            $url,
            $data
        );
        return $response;
    }
}
