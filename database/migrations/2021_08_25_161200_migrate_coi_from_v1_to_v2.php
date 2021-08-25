<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateCoiFromV1ToV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::rename('cois', 'cois_v1');
        // Schema::rename('cois_v2', 'cois');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::rename('cois', 'cois_v2');
        // Schema::rename('cois_v1', 'cois');
    }
}
