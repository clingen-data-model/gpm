<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Role;
use App\Models\Permission;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Group\Actions\MemberAssignRole;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use App\Modules\Group\Actions\MemberGrantPermissions;

/**
 * @group members
 */
class IsGroupMemberTest extends TestCase
{
    use FastRefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->group = Group::factory()->create();
        $this->person = Person::factory()->create();
        $this->groupPerm = Permission::factory()->create(['name' => 'test-permission', 'scope' => 'group']);
        $this->groupRole = Role::factory()->create(['name' => 'group-role', 'scope'=>'group']);
        $this->groupRolePerm = Permission::factory()->create();
        $this->groupRole->givePermissionTo($this->groupRolePerm);
        $groupMember = MemberAdd::run($this->group, $this->person);
        MemberAssignRole::run($groupMember, collect([$this->groupRole]));
        MemberGrantPermissions::run($groupMember, collect([$this->groupPerm]));
    }

    /**
     * @test
     */
    public function determines_if_person_has_permission_for_group()
    {
        $this->assertTrue($this->person->hasGroupPermissionTo('test-permission', collect([$this->group])));

        $this->assertTrue($this->person->hasGroupPermissionTo($this->groupRolePerm->name, collect([$this->group])));

        Permission::factory()->create(['name' => 'another-group-perm', 'scope' => 'group']);

        $this->assertFalse($this->person->hasGroupPermissionTo('another-group-perm', collect([$this->group])));
    }
}
