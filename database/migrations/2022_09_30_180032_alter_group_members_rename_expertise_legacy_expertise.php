<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterGroupMembersRenameExpertiseLegacyExpertise extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->renameColumn('expertise', 'legacy_expertise');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->renameColumn('legacy_expertise', 'expertise');
        });
    }
}
