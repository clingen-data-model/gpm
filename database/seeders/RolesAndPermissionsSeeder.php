<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()['cache']->forget('spatie.permission.cache');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $config = json_decode(file_get_contents(base_path('database/seeders/roles_and_permissions.json')), true);
        $permissions = $config['permissions']['singles'];
        $permissionGroups = $config['permissions']['groups'];
        $roles = $config['roles'];

        DB::beginTransaction();
        foreach ($permissions as $perm) {
            Permission::firstOrcreate(['name' => $perm]);
        }

        foreach ($permissionGroups as $group => $perms) {
            $this->createPermissionGroup($group, $perms);
        }

        foreach ($roles as $role) {
            $roleModel = Role::firstOrCreate(['name' => $role['name']]);
            if ($role['permissions']['singles'] == '*') {
                $this->givePermissionsToRole($roleModel, $permissions);
            } else if (is_array($role['permissions']['singles'])) {
                $this->givePermissionsToRole($roleModel, $role['permissions']['singles']);
            }

            if ($role['permissions']['groups'] == '*') {
                foreach ($permissionGroups as $group => $perms) {
                    $this->giveActionPermissionsToRole($roleModel, $group, $perms);
                }
            } else if (is_array($role['permissions']['groups'])) {
                foreach ($role['permissions']['groups'] as $group) {
                    if ($group['permissions'] == '*') {
                        $this->giveActionPermissionsToRole(
                            $roleModel, 
                            $group['name'], 
                            $permissionGroups[$group['name']]
                        );
                     } elseif (is_array($group['permissions'])) {
                        $this->giveActionPermissionsToRole(
                            $roleModel, 
                            $group['name'], 
                            $group['permissions']
                        );
                    }
                }    
            }

        }
        DB::commit();

    }

    protected function giveActionPermissionsToRole($role, $entity, ?Array $actions = null)
    {
        $actions = $actions ?? ['list', 'create', 'update', 'delete'];
        foreach ($actions as $action) {
            $perm = $this->formatPermName($entity, $action);
            if (!$role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }
    }

    protected function createPermissionGroup($entity, $actions = null)
    {
        $actions = $actions ?? ['list', 'create', 'update', 'delete'];
        foreach ($actions as $action) {
            $perm = $this->formatPermName($entity, $action);
            Permission::firstOrcreate(['name' => $perm]);
        }
    }

    protected function givePermissionToRole(Role $role, string $perm)
    {
        if (!$role->hasPermissionTo($perm)) {
            $role->givePermissionTo($perm);
        }
    }

    protected function givePermissionsToRole(Role $role, Array $perms)
    {
        foreach ($perms as $perm) {
            $this->givePermissionToRole($role, $perm);
        }
    }

    private function formatPermName($entity, $action) {
        return $entity.'-'.$action;
    }

}
