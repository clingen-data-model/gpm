<?php

namespace Tests\Feature\End2End\Groups\Members;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Actions\MemberGrantPermissions;
use App\Modules\Person\Notifications\InviteNotification;

/**
 * @group groups
 * @group members
 */
class InviteMemberTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->user->person()->create(Person::factory()->make()->toArray());

        $this->group = Group::factory()->create();
        $this->userMember = MemberAdd::run($this->group, $this->user->person);

        $this->url = 'api/groups/'.$this->group->uuid.'/invites';
    }

    /**
     * @test
     */
    public function guests_cannot_invite_members()
    {
        $this->json('POST', $this->url, [])
            ->assertStatus(401);
    }
    

    /**
     * @test
     */
    public function unprivileged_member_cannot_invite_members()
    {
        Sanctum::actingAs($this->user);
        $this->json('POST', $this->url, [])
            ->assertStatus(403);
    }

    
    /**
     * @test
     */
    public function validates_data()
    {
        MemberGrantPermissions::run(
            $this->userMember,
            collect([config('permission.models.permission')::factory()->create(['name' => 'members-invite', 'scope' => 'group'])])
        );

        Sanctum::actingAs($this->user->fresh());
        $response = $this->json('POST', $this->url, []);
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'first_name' => ['A first name is required.'],
            'last_name' => ['A last name is required.'],
            'email' => ['An email is required.'],
        ]);
    }
    

    /**
     * @test
     */
    public function privileged_member_can_invite_new_member()
    {
        MemberGrantPermissions::run(
            $this->userMember,
            collect([config('permission.models.permission')::factory()->create(['name' => 'members-invite', 'scope' => 'group'])])
        );

        Sanctum::actingAs($this->user->fresh());
        $response = $this->json('POST', $this->url, [
            'first_name' => 'Test',
            'last_name' => 'Testerson',
            'email' => 'test@test.com',
        ]);
        $response->assertStatus(201);

        $response->assertJsonFragment([
            'first_name' => 'Test',
            'last_name' => 'Testerson',
            'email' => 'test@test.com',
        ]);

        $response->assertJsonFragment([
            'group_id' => $this->group->id,
        ]);

        $this->assertDatabaseHas('invites', [
            'email' => 'test@test.com',
            'inviter_id' => $this->group->id,
            'inviter_type' => get_class($this->group),
            'first_name' => 'Test',
            'last_name' => 'Testerson',
            'redeemed_at' => null
        ]);

        $this->assertDatabaseHas('people', [
            'user_id' => null,
            'email' => 'test@test.com',
            'first_name' => 'Test',
            'last_name' => 'Testerson',
        ]);

        $newPerson = Person::orderBy('id', 'desc')->first();
        $this->assertNotEquals($this->user->person->id, $newPerson->id);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'person_id' => $newPerson->id
        ]);
    }

    /**
     * @test
     */
    public function invite_can_also_give_roles_to_invited_member()
    {
        MemberGrantPermissions::run(
            $this->userMember,
            collect([config('permission.models.permission')::factory()->create(['name' => 'members-invite', 'scope' => 'group'])])
        );

        $role = config('permission.models.role')::factory(['scope' => 'group'])->create();

        Sanctum::actingAs($this->user->fresh());
        $response = $this->json('POST', $this->url, [
            'first_name' => 'Test',
            'last_name' => 'Testerson',
            'email' => 'test@test.com',
            'inviter_id' => $this->group->id,
            'inviter_type' => get_class($this->group),
            'role_ids' => [$role->id]
        ]);
        $response->assertStatus(201);
        $this->assertEquals($role->id, $response->original->roles[0]->id);
    }
    

    /**
     * @test
     */
    public function logs_invite_created_activity()
    {
        MemberGrantPermissions::run(
            $this->userMember,
            collect([config('permission.models.permission')::factory()->create(['name' => 'members-invite', 'scope' => 'group'])])
        );

        Sanctum::actingAs($this->user->fresh());
        $response = $this->json('POST', $this->url, [
            'first_name' => 'Test',
            'last_name' => 'Testerson',
            'email' => 'test@test.com',
            'inviter_id' => $this->group->id,
            'inviter_type' => get_class($this->group)
        ]);
        $response->assertStatus(201);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Person::class,
            'subject_id' => $response->original->person_id,
            'activity_type' => 'person-invited',
            'properties->first_name' => 'Test',
            'properties->last_name' => 'Testerson',
            'properties->email' => 'test@test.com',
            'properties->inviter_id' => $this->group->id,
            'properties->inviter_type' => Group::class,
        ]);
    }

    /**
     * @test
     */
    public function invitee_is_sent_an_email_notification_about_the_invite()
    {
        MemberGrantPermissions::run(
            $this->userMember,
            collect([config('permission.models.permission')::factory()->create(['name' => 'members-invite', 'scope' => 'group'])])
        );

        Sanctum::actingAs($this->user->fresh());
        
        Notification::fake();
        $response = $this->json('POST', $this->url, [
            'first_name' => 'Test',
            'last_name' => 'Testerson',
            'email' => 'test@test.com',
            'inviter_id' => $this->group->id,
            'inviter_type' => get_class($this->group)
        ]);
        $response->assertStatus(201);
        
        $newPerson = Person::orderBy('id', 'desc')->first();
        Notification::assertSentTo($newPerson, InviteNotification::class);
    }

    /**
     * @test
     */
    public function saves_is_contact_attribute()
    {
        MemberGrantPermissions::run(
            $this->userMember,
            collect([config('permission.models.permission')::factory()->create(['name' => 'members-invite', 'scope' => 'group'])])
        );

        Sanctum::actingAs($this->user->fresh());
        $response = $this->json('POST', $this->url, [
            'first_name' => 'Test',
            'last_name' => 'Testerson',
            'email' => 'test@test.com',
            'is_contact' => true
        ]);
        $response->assertStatus(201);

        $newPerson = Person::orderBy('id', 'desc')->first();
        $this->assertNotEquals($this->user->person->id, $newPerson->id);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'person_id' => $newPerson->id,
            'is_contact' => 1
        ]);
    }
}
