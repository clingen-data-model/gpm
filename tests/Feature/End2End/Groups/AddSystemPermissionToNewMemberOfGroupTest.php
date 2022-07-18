<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use App\Models\FollowAction;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Events\MemberAdded;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Person\Actions\PermissionAdd;
use App\Modules\Person\Events\InviteRedeemed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Actions\MemberAddSystemPermission;

class AddSystemPermissionToNewMemberOfGroupTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->group = Group::factory()->create();
        $this->permission = $this->setupPermission(['test-perm'], 'system')[0];

        FollowAction::create([
            'event_class' => MemberAdded::class,
            'follower' => MemberAddSystemPermission::class,
            'args' => ['groupId' => $this->group->id, 'permissionName' => 'test-perm']
        ]);
    }

    /**
     * @test
     */
    public function permission_given_to_activated_person_when_added_to_group()
    {
        $user = $this->setupUserWithPerson();

        $memberAdd = app()->make(MemberAdd::class);
        $memberAdd->handle($this->group, $user->person);

        $this->assertDatabaseHas('model_has_permissions', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'permission_id' => $this->permission->id
        ]);
    }

    /**
     * @test
     */
    public function permission_not_added_to_activited_person_when_added_to_other_group()
    {
        $user = $this->setupUserWithPerson();
        $otherGroup = Group::factory()->create();

        $memberAdd = app()->make(MemberAdd::class);
        $memberAdd->handle($otherGroup, $user->person);

        $this->assertDatabaseMissing('model_has_permissions', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'permission_id' => $this->permission->id
        ]);
    }
    
    /**
     * @test
     */
    public function adds_follow_action_if_person_not_activated()
    {
        $person = Person::factory()->create();

        $memberAdd = app()->make(MemberAdd::class);
        $memberAdd->handle($this->group, $person);

        $this->assertDatabaseHas('follow_actions', [
            'name' => 'Grant test-perm permission to '.$person->name.' when invite redeemed.',
            'event_class' => InviteRedeemed::class,
            'follower' => PermissionAdd::class,
            'args->personId' => $person->id,
            'args->permissionName' => $this->permission->name,
        ]);
    }

    
}
