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
use App\Modules\Group\Actions\MemberRemove;
use App\Modules\Group\Actions\MemberRemoveSystemPermission;
use App\Modules\Group\Actions\MemberRetire;
use App\Modules\Group\Events\MemberRemoved;
use App\Modules\Group\Events\MemberRetired;
use Carbon\Carbon;

class RemoveSystemPermissionFromRemovedOrRetiredGroupMemberTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->group = Group::factory()->create();
        $this->permission = $this->setupPermission(['test-perm'], 'system')[0];

        FollowAction::create([
            'event_class' => MemberRemoved::class,
            'follower' => MemberRemoveSystemPermission::class,
            'args' => ['groupId' => $this->group->id, 'permissionName' => 'test-perm']
        ]);

        FollowAction::create([
            'event_class' => MemberRetired::class,
            'follower' => MemberRemoveSystemPermission::class,
            'args' => ['groupId' => $this->group->id, 'permissionName' => 'test-perm']
        ]);
    }

    /**
     * @test
     */
    public function permission_removed_when_member_removed()
    {
        $user = $this->setupUserWithPerson(permissions:['test-perm']);
        $groupMember = app()->make(MemberAdd::class)->handle($this->group, $user->person);

        app()->make(MemberRemove::class)->handle($groupMember, Carbon::now());

        $this->assertDatabaseMissing('model_has_permissions', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'permission_id' => $this->permission->id
        ]);
    }

    /**
     * @test
     */
    public function permission_removed_when_member_retired()
    {
        $user = $this->setupUserWithPerson(permissions:['test-perm']);
        $groupMember = app()->make(MemberAdd::class)->handle($this->group, $user->person);

        app()->make(MemberRetire::class)->handle($groupMember, Carbon::now());

        $this->assertDatabaseMissing('model_has_permissions', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'permission_id' => $this->permission->id
        ]);
    }

    /**
     * @test
     */
    public function permission_not_removed_to_activited_person_when_added_to_other_group()
    {
        $user = $this->setupUserWithPerson(permissions: ['test-perm']);
        $otherGroup = Group::factory()->create();

        $memberAdd = app()->make(MemberAdd::class);
        $memberAdd->handle($otherGroup, $user->person);

        $this->assertDatabaseHas('model_has_permissions', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'permission_id' => $this->permission->id
        ]);
    }
    
}
