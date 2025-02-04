<?php

namespace Tests\Feature\End2End\Users;

use Tests\TestCase;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class UpdateUserRolesAndPermissionsTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->runSeeder(RolesAndPermissionsSeeder::class);
        
        $this->actingUser = $this->setupUser(permissions: ['users-manage']);
        Sanctum::actingAs($this->actingUser);

        $this->user = $this->setupUserWithPerson(permissions: ['annual-updates-manage']);
        $this->user->assignRole('admin');
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_update_user_roles_and_permissions()
    {
        $this->actingUser->revokePermissionTo('users-manage');

        $this->makeRequest()
            ->assertStatus(403);

        // $this->assertUserHasRole(config('system.roles.admin.id'));
        // $this->assertUserMissingRole(config('system.roles.super-admin.id'));

        // $this->assertUserHasPermission(config('system.permissions.annual-updates-manage.id'));
        // $this->assertUserMissingPermission(config('system.permissions.ep-applications-manage.id'));
    }

    /**
     * @test
     */
    public function privileged_can_update_user_roles_and_permissions()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->user->id,
                'name' => $this->user->name
            ]);

        $this->assertUserMissingRole(config('system.roles.admin.id'));
        $this->assertUserHasRole(config('system.roles.super-admin.id'));
    
        $this->assertUserMissingPermission(config('system.permissions.annual-updates-manage.id'));
        $this->assertUserHasPermission(config('system.permissions.ep-applications-manage.id'));
    }
    
    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'role_ids' => [config('system.roles.super-admin.id')],
            'permission_ids' => [config('system.permissions.ep-applications-manage.id')]
        ];

        return $this->json('put', '/api/users/'.$this->user->id.'/roles-and-permissions', $data);
    }

    private function assertUserHasRole($roleId)
    {
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => get_class($this->user),
            'model_id' => $this->user->id,
            'role_id' => $roleId
        ]);
    }

    private function assertUserMissingRole($roleId)
    {
        $this->assertDatabaseMissing('model_has_roles', [
            'model_type' => get_class($this->user),
            'model_id' => $this->user->id,
            'role_id' => $roleId
        ]);
    }

    private function assertUserHasPermission($permissionId)
    {
        $this->assertDatabaseHas('model_has_permissions', [
            'model_type' => get_class($this->user),
            'model_id' => $this->user->id,
            'permission_id' => $permissionId
        ]);
    }

    private function assertUserMissingPermission($permissionId)
    {
        $this->assertDatabaseMissing('model_has_permissions', [
            'model_type' => get_class($this->user),
            'model_id' => $this->user->id,
            'permission_id' => $permissionId
        ]);
    }
}
