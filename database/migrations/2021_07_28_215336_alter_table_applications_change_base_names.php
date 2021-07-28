<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableApplicationsChangeBaseNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['short_base_name']);
            $table->dropUnique(['long_base_name']);

            $table->unique(['short_base_name', 'ep_type_id']);
            $table->unique(['long_base_name', 'ep_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['short_base_name', 'ep_type_id']);
            $table->dropUnique(['long_base_name', 'ep_type_id']);


            $table->unique('short_base_name');
            $table->unique('long_base_name');
        });
    }
}
