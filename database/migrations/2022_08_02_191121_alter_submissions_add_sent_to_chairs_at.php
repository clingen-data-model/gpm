<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterSubmissionsAddSentToChairsAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->datetime('sent_to_chairs_at')->nullable()->after('data');
            $table->text('notes_for_chairs')->nullable()->after('sent_to_chairs_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('sent_to_chairs_at');
            $table->dropColumn('notes_for_chairs');
        });
    }
}
