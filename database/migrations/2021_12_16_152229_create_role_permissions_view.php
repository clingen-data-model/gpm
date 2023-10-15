<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        DB::statement(
            "CREATE OR REPLACE VIEW role_permissions_view AS
                SELECT r.name AS 'role', p.name AS 'permission' 
                FROM roles r 
                    join role_has_permissions rp ON r.id = rp.role_id
                    join permissions p ON rp.permission_id = p.id;"
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        DB::statement('DROP VIEW IF EXISTS `view_user_data`;
        SQL;');
    }
};
