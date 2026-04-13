<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('permissions')->updateOrInsert(
                ['id' => 60],
                [
                    'name' => 'funding-sources-manage',
                    'display_name' => 'Manage Funding Sources',
                    'guard_name' => 'web',
                    'scope' => 'system',
                ]
            );

            DB::table('role_has_permissions')->insertOrIgnore([
                [
                    'permission_id' => 60,
                    'role_id' => 1, // super-user
                ],
                [
                    'permission_id' => 60,
                    'role_id' => 2, // super-admin
                ],
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            DB::table('role_has_permissions')
                ->where('permission_id', 60)
                ->whereIn('role_id', [1, 2])
                ->delete();

            DB::table('permissions')
                ->where('id', 60)
                ->where('name', 'funding-sources-manage')
                ->delete();
        });
    }
};
