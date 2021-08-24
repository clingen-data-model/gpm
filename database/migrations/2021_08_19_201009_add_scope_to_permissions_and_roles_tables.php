<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScopeToPermissionsAndRolesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->enum('scope', ['system', 'group'])->default('system');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->enum('scope', ['system', 'group'])->default('system');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
}
