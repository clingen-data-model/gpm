<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->text('description')->after('coi_code')->nullable();
        });

        DB::update('update `groups` set description = (select scope_description from expert_panels where expert_panels.group_id = `groups`.id)');

        Schema::table('expert_panels', function (Blueprint $table) {
            $table->dropColumn('scope_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->text('scope_description')->after('membership_description')->nullable();
        });

        DB::update('update `expert_panels` set scope_description = (select description from groups where expert_panels.group_id = `groups`.id)');

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
