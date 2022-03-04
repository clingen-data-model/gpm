<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCanBeParentToGroupTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('group_types', 'can_be_parent')) {
            return;
        }
        Schema::table('group_types', function (Blueprint $table) {
            $table->boolean('can_be_parent')->boolean()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_types', function (Blueprint $table) {
            $table->dropColumn('can_be_parent');
        });
    }
}
