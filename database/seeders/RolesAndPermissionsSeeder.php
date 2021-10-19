<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
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

        DB::beginTransaction();
            $this->seedFromConfig('system.roles', config('permission.models.role'));
            $this->seedFromConfig('system.permissions', config('permission.models.permission'));
            foreach (config('system.role_permissions') as $roleSlug => $permIds) {
                $rows = array_map(function ($permId) use ($roleSlug) {
                    return [
                            'role_id' => config('system.roles')[$roleSlug]['id'],
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
