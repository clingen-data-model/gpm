<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompletedAtToCois extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cois', function (Blueprint $table) {
            if (Schema::hasColumn('cois', 'completed_at')) {
                return;
            }

            $table->datetime('completed_at')->nullable()->after('expert_panel_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cois', function (Blueprint $table) {
            if (!Schema::hasColumn('cois', 'completed_at')) {
                return;
            }

            $table->dropColumn('completed_at');
        });
    }
}
