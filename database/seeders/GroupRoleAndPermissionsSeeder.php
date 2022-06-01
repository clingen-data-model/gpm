<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
use Illuminate\Support\Facades\DB;

class GroupRoleAndPermissionsSeeder extends Seeder
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
        $rolePermissions = config('groups.role_permissions');

        DB::beginTransaction();

        // DB::table('role_has_permissions')->delete();
        // DB::table('roles')->delete();
        // DB::table('permissions')->delete();

        $roles = collect(config('groups.roles'))->map(function ($role) {
            $role['scope'] = 'group';
            return $role;
        });
        $permissions = collect(config('groups.permissions'))->map(function ($permission) {
            $permission['scope'] = 'group';
            return $permission;
        });

        $this->seedFromArray($roles->toArray(), config('permission.models.role'));
        $this->seedFromArray($permissions->toArray(), config('permission.models.permission'));

        foreach (config('groups.role_permissions') as $roleSlug => $permIds) {
            $rows = array_map(function ($permId) use ($roleSlug) {
                return [
                        'role_id' => config('groups.roles')[$roleSlug]['id'],
                        'permission_id' => $permId
                    ];
            }, $permIds);
            foreach ($rows as $values) {
                DB::table('role_has_permissions')
                        ->updateOrInsert($values);
            }
        }
        DB::commit();
    }
}
