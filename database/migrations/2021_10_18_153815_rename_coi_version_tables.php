<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameCoiVersionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('cois_v1')) {
            return;
        }
        Schema::rename('cois', 'cois_v1');
        Schema::rename('cois_v2', 'cois');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('cois_v2')) {
            return;
        }
        Schema::rename('cois', 'cois_v2');
        Schema::rename('cois_v1', 'cois');
    }
}
